<?php

namespace App\Listeners;

use App\Events\ProductAfterStockAdjustmentEvent;
use App\Jobs\HandleStockAdjustmentJob;

class ProductAfterStockAdjustmentEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle( ProductAfterStockAdjustmentEvent $event )
    {
        HandleStockAdjustmentJob::dispatch( $event->history );
    }
}
