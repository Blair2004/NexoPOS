<?php

namespace App\Listeners;

use App\Events\OrderAfterUpdatedDeliveryStatus;
use App\Models\Order;
use App\Services\DriverService;
use Illuminate\Support\Facades\Log;

class OrderAfterUpdatedDeliveryStatusListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public DriverService $driverService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderAfterUpdatedDeliveryStatus $event): void
    {
        $order = $event->order;
        
        // Check if this is a delivery order with a driver that was just marked as delivered
        if (
            $order->driver_id &&
            $order->type === Order::TYPE_DELIVERY &&
            $order->delivery_status === Order::DELIVERY_DELIVERED &&
            $order->payment_status === Order::PAYMENT_PAID
        ) {
            try {
                $this->driverService->createEarningForDelivery($order, $order->driver_id);
            } catch (\Exception $e) {
                // Log the error but don't fail the delivery status update
                Log::warning('Failed to create driver commission on delivery status change: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'driver_id' => $order->driver_id
                ]);
            }
        }
    }
}
