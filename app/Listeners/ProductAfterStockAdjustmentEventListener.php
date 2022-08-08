<?php

namespace App\Listeners;

use App\Events\ProductAfterStockAdjustmentEvent;
use App\Jobs\HandleStockAdjustmentJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
     * @param  \App\Events\ProductAfterStockAdjustmentEvent  $event
     * @return void
     */
    public function handle(ProductAfterStockAdjustmentEvent $event)
    {
        HandleStockAdjustmentJob::dispatch( $event->history );
    }
}
