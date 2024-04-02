<?php

namespace App\Listeners;

use App\Events\ProductAfterCreatedEvent;
use App\Jobs\ComputeCategoryProductsJob;
use App\Services\BarcodeService;
use App\Services\ProductCategoryService;
use App\Services\ProductService;

class ProductAfterCreatedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProductService $productService,
        public BarcodeService $barcodeService,
        public ProductCategoryService $productCategoryService
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle( ProductAfterCreatedEvent $event )
    {
        $this->productService->generateProductBarcode( $event->product );

        ComputeCategoryProductsJob::dispatch( $event->product->category );
    }
}
