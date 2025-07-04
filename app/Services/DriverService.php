<?php
namespace App\Services;

use App\Classes\JsonResponse;
use App\Enums\DriverStatusEnum;
use App\Models\Driver;
use App\Models\DriverEarning;
use App\Models\DriverStatus;
use App\Models\Order;
use Exception;

class DriverService
{
    /**
     * Will change the driver status
     * @param Driver $driver
     * @param $status
     * @return JsonResponse
     */
    public function changeStatus( Driver $driver, $status )
    {
        $driverStatus   =   $driver->status()->first();

        /**
         * The driver status hasn't been created? We should
         * create it before setting any status.
         */
        if ( ! $status instanceof DriverStatus ) {
            $driverStatus   =   new DriverStatus;
        }

        $driverStatus->driver_id = $driver->id;
        $driverStatus->status = $status;
        $driverStatus->save();

        return JsonResponse::success(
            message: __( 'The driver status has been updated.' ),
            data: compact( 'driver', 'driverStatus' )
        );
    }

    public function getByStatus( $status )
    {
        return Driver::with([ 'billing', 'shipping', 'attribute' ])->whereHas( 'status', function( $query ) use ( $status ) {
            $query->where( 'status', $status );
        })->get();
    }

    /**
     * Create driver earning when delivery is marked as delivered.
     */
    public function createEarningForDelivery(Order $order, int $driverId): DriverEarning
    {
        // Check if earning already exists for this order and driver
        $existingEarning = DriverEarning::where('order_id', $order->id)
            ->where('driver_id', $driverId)
            ->first();

        if ($existingEarning) {
            throw new Exception(__('Earning record already exists for this delivery.'));
        }

        // Get payment settings
        $paymentType = ns()->option->get('ns_drivers_payment_type', 'fixed');
        $fixedRate = (float) ns()->option->get('ns_drivers_fixed_rate', 0);
        $percentageRate = (float) ns()->option->get('ns_drivers_percentage_rate', 10);

        // Get delivery fee from order
        $deliveryFee = $this->getOrderDeliveryFee($order);

        // Determine rate value based on payment type
        $rateValue = $paymentType === 'fixed' ? $fixedRate : $percentageRate;

        // Create earning record
        $earning = DriverEarning::create([
            'driver_id' => $driverId,
            'order_id' => $order->id,
            'payment_type' => $paymentType,
            'delivery_fee' => $deliveryFee,
            'rate_value' => $rateValue,
            'earning_amount' => 0, // Will be calculated next
            'status' => DriverEarning::STATUS_PENDING,
            'delivery_date' => now(),
        ]);

        // Calculate and update earning amount
        $earningAmount = $earning->calculateEarning();
        $earning->update(['earning_amount' => $earningAmount]);

        return $earning;
    }

    /**
     * Get delivery fee from order.
     */
    protected function getOrderDeliveryFee(Order $order): float
    {
        // Check if there's a delivery fee in order
        // This might need adjustment based on how delivery fees are stored in your system
        $deliveryFee = $order->shipping ?? 0;
        
        // If no delivery fee found, check in order metas or other fields
        if ($deliveryFee <= 0) {
            // You might need to adjust this based on your order structure
            $deliveryMeta = $order->metas()->where('key', 'delivery_fee')->first();
            $deliveryFee = $deliveryMeta ? (float) $deliveryMeta->value : 0;
        }

        return $deliveryFee;
    }

    /**
     * Calculate total earnings for a driver in a date range.
     */
    public function calculateDriverEarnings( Driver $driver, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = DriverEarning::forDriver($driver);

        if ($startDate) {
            $query->where('delivery_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('delivery_date', '<=', $endDate);
        }

        $earnings = $query->get();

        return [
            'total_deliveries' => $earnings->count(),
            'total_earnings' => $earnings->sum('earning_amount'),
            'paid_earnings' => $earnings->where('status', DriverEarning::STATUS_PAID)->sum('earning_amount'),
            'pending_earnings' => $earnings->where('status', DriverEarning::STATUS_PENDING)->sum('earning_amount'),
            'cancelled_earnings' => $earnings->where('status', DriverEarning::STATUS_CANCELLED)->sum('earning_amount'),
            'earnings_breakdown' => $earnings->groupBy('payment_type')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('earning_amount'),
                ];
            }),
        ];
    }

    /**
     * Mark multiple earnings as paid.
     */
    public function markEarningsAsPaid(array $earningIds): int
    {
        $count = DriverEarning::whereIn('id', $earningIds)
            ->where('status', DriverEarning::STATUS_PENDING)
            ->update([
                'status' => DriverEarning::STATUS_PAID,
                'paid_date' => now()
            ]);

        return $count;
    }

    /**
     * Get driver statistics.
     */
    public function getDriverStats( Driver $driver ): array
    {
        // Get earnings for different periods
        $todayEarnings = $this->calculateDriverEarnings($driver, now()->startOfDay(), now()->endOfDay());
        $weekEarnings = $this->calculateDriverEarnings($driver, now()->startOfWeek(), now()->endOfWeek());
        $monthEarnings = $this->calculateDriverEarnings($driver, now()->startOfMonth(), now()->endOfMonth());
        $allTimeEarnings = $this->calculateDriverEarnings($driver);

        return [
            'driver' => $driver,
            'today' => $todayEarnings,
            'this_week' => $weekEarnings,
            'this_month' => $monthEarnings,
            'all_time' => $allTimeEarnings,
            'status' => $driver->status?->status ?? Driver::STATUS_OFFLINE,
        ];
    }
}