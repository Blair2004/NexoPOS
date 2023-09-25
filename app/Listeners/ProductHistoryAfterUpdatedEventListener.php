<?php

namespace App\Listeners;

use App\Events\ProductHistoryAfterUpdatedEvent;
use App\Services\ProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProductHistoryAfterUpdatedEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected ProductService $productService
    )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( ProductHistoryAfterUpdatedEvent $event): void
    {
        $this->productService->computeCogsIfNecessary( $event->productHistory );
    }
}
