<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverEarning;
use App\Models\Order;
use Exception;

class DriverEarningService
{
    /**
     * Create driver earning when delivery order is created.
     */
    public function createEarningForOrder(Order $order): ?DriverEarning
    {
        // Only process delivery orders
        if ($order->type !== Order::TYPE_DELIVERY) {
            return null;
        }

        // Check if driver feature is enabled
        if (ns()->option->get('ns_drivers_enabled', 'no') !== 'yes') {
            return null;
        }

        // Get driver ID from order (assuming it's stored in a meta or field)
        $driverId = $this->getDriverIdFromOrder($order);
        
        if (!$driverId) {
            return null;
        }

        // Check if earning already exists for this order and driver
        $existingEarning = DriverEarning::where('order_id', $order->id)
            ->where('driver_id', $driverId)
            ->first();

        if ($existingEarning) {
            return $existingEarning;
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
            'delivery_date' => null, // Will be set when delivered
        ]);

        // Calculate and update earning amount
        $earningAmount = $earning->calculateEarning();
        $earning->update(['earning_amount' => $earningAmount]);

        return $earning;
    }

    /**
     * Update earning when delivery is completed.
     */
    public function updateEarningOnDelivery(Order $order): ?DriverEarning
    {
        // Only process delivery orders
        if ($order->type !== Order::TYPE_DELIVERY) {
            return null;
        }

        // Check if order is delivered
        if ($order->delivery_status !== Order::DELIVERY_DELIVERED) {
            return null;
        }

        // Get driver ID from order
        $driverId = $this->getDriverIdFromOrder($order);
        
        if (!$driverId) {
            return null;
        }

        // Find existing earning record
        $earning = DriverEarning::where('order_id', $order->id)
            ->where('driver_id', $driverId)
            ->first();

        if (!$earning) {
            // Create earning if it doesn't exist (fallback)
            return $this->createEarningForOrder($order);
        }

        // Update delivery date if not already set
        if (!$earning->delivery_date) {
            $earning->update([
                'delivery_date' => now()
            ]);
        }

        return $earning;
    }

    /**
     * Get driver ID from order.
     */
    protected function getDriverIdFromOrder(Order $order): ?int
    {
        // Check if there's a driver_id field directly on the order
        if (isset($order->driver_id) && $order->driver_id) {
            return $order->driver_id;
        }

        return null;
    }

    /**
     * Get delivery fee from order.
     */
    protected function getOrderDeliveryFee(Order $order): float
    {
        // Check if there's a delivery fee in order
        $deliveryFee = $order->shipping ?? 0;
        
        // If no delivery fee found, check in order metas
        if ($deliveryFee <= 0) {
            $deliveryMeta = $order->metas()->where('key', 'delivery_fee')->first();
            $deliveryFee = $deliveryMeta ? (float) $deliveryMeta->value : 0;
        }

        return $deliveryFee;
    }

    /**
     * Calculate total earnings for a driver in a date range.
     */
    public function calculateDriverEarnings(int $driverId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = DriverEarning::forDriver($driverId);

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
    public function getDriverStats(int $driverId): array
    {
        $driver = Driver::find($driverId);
        
        if (!$driver) {
            throw new Exception(__('Driver not found.'));
        }

        // Get earnings for different periods
        $todayEarnings = $this->calculateDriverEarnings($driverId, now()->startOfDay(), now()->endOfDay());
        $weekEarnings = $this->calculateDriverEarnings($driverId, now()->startOfWeek(), now()->endOfWeek());
        $monthEarnings = $this->calculateDriverEarnings($driverId, now()->startOfMonth(), now()->endOfMonth());
        $allTimeEarnings = $this->calculateDriverEarnings($driverId);

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
