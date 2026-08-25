<?php

namespace App\Listeners;

use App\Events\ProductBeforeDeleteEvent;
use App\Events\ProductBeforeDeleteProductEvent;
use App\Services\ProductService;

class ProductBeforeDeleteEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProductService $productService
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @param  ProductBeforeDeleteProductEvent $event
     * @return void
     */
    public function handle( ProductBeforeDeleteEvent $event )
    {
        $this->productService->deleteProductRelations( $event->product );
    }
}
