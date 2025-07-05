<?php

namespace App\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Services\DriverEarningService;

class DriverEarningOrderCreatedListener
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
    public function handle(OrderAfterCreatedEvent $event): void
    {
        try {
            // Create driver earning for delivery orders
            $this->driverEarningService->createEarningForOrder($event->order);
        } catch (\Exception $e) {
            // Log error but don't break the order creation process
            logger()->error('Failed to create driver earning for order', [
                'order_id' => $event->order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
