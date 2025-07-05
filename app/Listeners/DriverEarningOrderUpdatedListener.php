<?php

namespace App\Listeners;

use App\Events\OrderAfterUpdatedEvent;
use App\Models\Order;
use App\Services\DriverEarningService;

class DriverEarningOrderUpdatedListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public DriverEarningService $driverEarningService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderAfterUpdatedEvent $event): void
    {
        try {
            // Check if this is a delivery order and status changed to delivered
            if ($event->order->type === Order::TYPE_DELIVERY && 
                $event->order->delivery_status === Order::DELIVERY_DELIVERED) {
                
                $this->driverEarningService->updateEarningOnDelivery($event->order);
            }
        } catch (\Exception $e) {
            // Log error but don't break the order update process
            logger()->error('Failed to update driver earning on order update', [
                'order_id' => $event->order->id,
                'order_type' => $event->order->type,
                'delivery_status' => $event->order->delivery_status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
