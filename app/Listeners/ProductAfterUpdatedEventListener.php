<?php

namespace App\Listeners;

use App\Events\ProductAfterUpdatedEvent;
use App\Jobs\ComputeCategoryProductsJob;
use App\Services\ProductService;

class ProductAfterUpdatedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProductService $productService,
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(ProductAfterUpdatedEvent $event)
    {
        $this->productService->generateProductBarcode( $event->product );

        ComputeCategoryProductsJob::dispatch( $event->product->category );
    }
}
