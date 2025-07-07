<?php
namespace App\Services;

use App\Events\OrderAfterUpdatedDeliveryStatus;
use App\Models\Driver;
use App\Models\DriverEarning;
use App\Models\DriverStatus;
use App\Models\Order;
use App\Models\OrderDeliveryProof;
use App\Models\OrderPayment;
use App\Models\PaymentType;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DriverService
{
    /**
     * Will change the driver status
     * @param Driver $driver
     * @param $status
     */
    public function changeStatus( Driver $driver, $status ): array
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

        return [
            'status' => 'success',
            'message' => __('Driver status has been changed successfully.'),
            'data' => [
                'driver' => $driver->fresh(),
                'status' => $driverStatus->status,
            ]
        ];
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
    public function calculateDriverEarnings( Driver $driver, $startDate = null, $endDate = null): array
    {
        $query = DriverEarning::forDriver($driver);

        if ($startDate) {
            // Convert to string if it's a Carbon instance
            $startDateString = $startDate instanceof Carbon ? $startDate->toDateTimeString() : $startDate;
            $query->where('delivery_date', '>=', $startDateString);
        }

        if ($endDate) {
            // Convert to string if it's a Carbon instance
            $endDateString = $endDate instanceof Carbon ? $endDate->toDateTimeString() : $endDate;
            $query->where('delivery_date', '<=', $endDateString);
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

    /**
     * Update order delivery status and handle payments
     *
     * @param Order $order
     * @param array $data
     * @throws Exception
     */
    public function updateOrder(Order $order, array $data): array
    {
        try {
            return DB::transaction(function () use ($order, $data) {
                // Validate required fields
                $this->validateOrderUpdateData($data);

                // Handle optional payment method validation
                $paymentType = null;
                if (!empty($data['payment_method'])) {
                    $paymentType = PaymentType::where('identifier', $data['payment_method'])->first();
                    if (!$paymentType) {
                        throw new Exception(__('Invalid payment method provided.'));
                    }
                }

                // Create or update delivery proof record
                $deliveryProof = OrderDeliveryProof::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'driver_id' => $data['driver_id'] ?? $order->driver_id
                    ],
                    [
                        'is_delivered' => (bool) $data['is_delivered'],
                        'delivery_proof' => $data['delivery_proof'],
                        'note' => $data['note'],
                        'paid_on_delivery' => $order->payment_status === Order::PAYMENT_PAID ? true : (isset($data['paid_on_delivery']) ? (bool) $data['paid_on_delivery'] : false),
                    ]
                );

                // Handle order status and payment updates
                $this->handleOrderStatusUpdate($order, $data, $paymentType);

                // Process commission if order is delivered
                if ((bool) $data['is_delivered'] && $order->delivery_status === Order::DELIVERY_ONGOING) {
                    $this->processDeliveryCompletion($order, $data['driver_id'] ?? $order->driver_id);
                }

                return [
                    'status' => 'success',
                    'message' => __('Order delivery status has been updated successfully.'),
                    'data' => [
                        'order' => $order->fresh(),
                        'delivery_proof' => $deliveryProof,
                        'payment_type' => $paymentType ? $paymentType->identifier : null,
                        'driver_id' => $data['driver_id'] ?? $order->driver_id,
                    ]
                ];
            });
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate order update data
     *
     * @param array $data
     * @throws Exception
     */
    protected function validateOrderUpdateData(array $data): void
    {
        $required = ['is_delivered', 'delivery_proof', 'note'];
        
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new Exception(__("The field :field is required.", ['field' => $field]));
            }
        }

        // Validate boolean fields
        if (!in_array($data['is_delivered'], [0, 1, '0', '1', true, false])) {
            throw new Exception(__('The is_delivered field must be 0 or 1.'));
        }

        if (isset($data['paid_on_delivery']) && !in_array($data['paid_on_delivery'], [0, 1, '0', '1', true, false])) {
            throw new Exception(__('The paid_on_delivery field must be 0 or 1.'));
        }
    }

    /**
     * Handle order status and payment updates
     *
     * @param Order $order
     * @param array $data
     * @param PaymentType|null $paymentType
     * @throws Exception
     */
    protected function handleOrderStatusUpdate(Order $order, array $data, ?PaymentType $paymentType): void
    {
        $isDelivered = (bool) $data['is_delivered'];
        $paidOnDelivery = isset($data['paid_on_delivery']) ? (bool) $data['paid_on_delivery'] : null;

        // Update order delivery status
        if ($isDelivered) {
            $order->delivery_status = Order::DELIVERY_DELIVERED;
        } else {
            $order->delivery_status = Order::DELIVERY_FAILED;
        }

        // Handle payment method update
        if (!empty($data['payment_method'])) {
            // You might need to update order payment method here based on your order structure
            // This could be stored in order metas or a specific field
        }

        // Handle paid_on_delivery logic
        if ($paidOnDelivery !== null) {
            if ($paidOnDelivery && $isDelivered) {
                // Create payment entry for delivered order
                $this->createDeliveryPayment($order, $paymentType);
            } elseif (!$paidOnDelivery) {
                // Mark delivery as failed if payment was not received
                $order->delivery_status = Order::DELIVERY_FAILED;
            }
        }

        $order->save();
    }

    /**
     * Create payment entry for delivery
     *
     * @param Order $order
     * @param PaymentType|null $paymentType
     * @throws Exception
     */
    protected function createDeliveryPayment(Order $order, ?PaymentType $paymentType): void
    {
        // Calculate due amount
        $dueAmount = $order->total - $order->tendered;

        if ($dueAmount > 0) {
            $payment = new OrderPayment();
            $payment->order_id = $order->id;
            $payment->value = $dueAmount;
            $payment->author = Auth::id() ?? $order->author;
            $payment->identifier = $paymentType ? $paymentType->identifier : OrderPayment::PAYMENT_CASH;
            $payment->uuid = Str::uuid()->toString();
            $payment->save();

            // Update order tendered amount
            $order->tendered += $dueAmount;
            
            // Update payment status if fully paid
            if ($order->tendered >= $order->total) {
                $order->payment_status = Order::PAYMENT_PAID;
            }
        }
    }

    /**
     * Process delivery completion and commission
     *
     * @param Order $order
     * @param int $driverId
     */
    protected function processDeliveryCompletion(Order $order, int $driverId): void
    {
        try {
            // Create driver earning for the delivery
            $this->createEarningForDelivery($order, $driverId);
        } catch (Exception $e) {
            // Log the error but don't fail the entire operation
            Log::warning('Failed to create driver earning: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'driver_id' => $driverId
            ]);
        }
    }

    /**
     * Start a delivery by changing status from pending to ongoing
     *
     * @param Order $order
     * @param int $driverId
     * @throws Exception
     */
    public function startDelivery(Order $order, int $driverId): array
    {
        try {
            return DB::transaction(function () use ($order, $driverId) {
                // Validate that this is a delivery order with pending status
                if ($order->type !== Order::TYPE_DELIVERY) {
                    throw new Exception(__('This is not a delivery order.'));
                }

                if ($order->delivery_status !== Order::DELIVERY_PENDING) {
                    throw new Exception(__('This delivery is not in pending status.'));
                }

                if ($order->driver_id !== $driverId) {
                    throw new Exception(__('You are not assigned to this delivery.'));
                }

                // Update delivery status to ongoing
                $order->delivery_status = Order::DELIVERY_ONGOING;
                $order->save();

                // Dispatch event for delivery status change
                event(new OrderAfterUpdatedDeliveryStatus($order));

                return [
                    'status' => 'success',
                    'message' => __('Delivery has been started successfully.'),
                    'data' => [
                        'order' => $order->fresh(),
                    ]
                ];
            });
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Reject a delivery by unassigning driver and keeping status as pending
     *
     * @param Order $order
     * @param int $driverId
     * @return array
     * @throws Exception
     */
    public function rejectDelivery(Order $order, int $driverId): array
    {
        try {
            return DB::transaction(function () use ($order, $driverId) {
                // Validate that this is a delivery order with pending status
                if ($order->type !== Order::TYPE_DELIVERY) {
                    throw new Exception(__('This is not a delivery order.'));
                }

                if ($order->delivery_status !== Order::DELIVERY_PENDING) {
                    throw new Exception(__('This delivery is not in pending status.'));
                }

                if ($order->driver_id !== $driverId) {
                    throw new Exception(__('You are not assigned to this delivery.'));
                }

                // Unassign driver and keep delivery status as pending
                $order->driver_id = null;
                $order->save();

                return [
                    'status' => 'success',
                    'message' => __('Delivery has been rejected successfully.'),
                    'data' => [
                        'order' => $order->fresh(),
                    ]
                ];
            });
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}