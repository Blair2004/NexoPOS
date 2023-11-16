<?php

namespace App\Listeners;

use App\Events\ProductBeforeDeleteEvent;
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
     * @param  \App\Events\ProductBeforeDeleteProductEvent  $event
     * @return void
     */
    public function handle(ProductBeforeDeleteEvent $event)
    {
        /**
         * This will be useful after deleting the product
         * to recount all the items linked to that category.
         */
        $event->product->load( 'category' );

        /**
         * @todo check if the product is currently
         * in use within a procurement or an unpaid order
         */
        $this->productService->resetProduct( $event->product );

        $this->productService->getProductVariations( $event->product )->each( function ( $variation ) {
            /**
             * deleting a variation
             * could also trigger the same product event
             * and it could be cached or tweaked
             */
            $this->productService->deleteVariations( $variation );
        });
    }
}
