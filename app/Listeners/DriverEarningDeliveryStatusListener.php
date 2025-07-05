<?php

namespace App\Listeners;

use App\Events\OrderAfterUpdatedDeliveryStatus;
use App\Services\DriverEarningService;

class DriverEarningDeliveryStatusListener
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
    public function handle(OrderAfterUpdatedDeliveryStatus $event): void
    {
        try {
            // Update driver earning when delivery is completed
            $this->driverEarningService->updateEarningOnDelivery($event->order);
        } catch (\Exception $e) {
            // Log error but don't break the delivery status update process
            logger()->error('Failed to update driver earning for delivery', [
                'order_id' => $event->order->id,
                'delivery_status' => $event->order->delivery_status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
