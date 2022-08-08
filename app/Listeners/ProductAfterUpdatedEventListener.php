<?php

namespace App\Listeners;

use App\Events\ProductAfterUpdatedEvent;
use App\Services\ProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProductAfterUpdatedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProductService $productService
    )
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ProductAfterUpdatedEvent  $event
     * @return void
     */
    public function handle(ProductAfterUpdatedEvent $event)
    {
        $this->productService->generateProductBarcode( $event->product );
    }
}
