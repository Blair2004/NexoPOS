<?php

namespace App\Listeners;

use App\Events\ProductAfterCreatedEvent;
use App\Services\BarcodeService;
use App\Services\ProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProductAfterCreatedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProductService $productService,
        public BarcodeService $barcodeService
    )
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ProductAfterCreatedEvent  $event
     * @return void
     */
    public function handle(ProductAfterCreatedEvent $event)
    {
        $this->productService->generateProductBarcode( $event->product );

        /**
         * create jobs that count product categories.
         */        
    }
}
