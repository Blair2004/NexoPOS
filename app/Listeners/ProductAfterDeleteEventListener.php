<?php

namespace App\Listeners;

use App\Events\ProductAfterDeleteEvent;
use App\Services\ProductCategoryService;
use App\Services\ProductService;

class ProductAfterDeleteEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProductService $productService,
        public ProductCategoryService $productCategoryService,
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle( ProductAfterDeleteEvent $event )
    {
        $this->productCategoryService->computeProducts( $event->product->category );
    }
}
