<?php

namespace App\Listeners;

use App\Events\ProductBeforeDeleteEvent;
use App\Services\ProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProductBeforeDeleteEventListener
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
     * @param  \App\Events\ProductBeforeDeleteProductEvent  $event
     * @return void
     */
    public function handle(ProductBeforeDeleteEvent $event)
    {
        /**
         * @todo check if the product is currently
         * in use within a procurement or an unpaid order
         */
        $this->productService->resetProduct( $event->product );

        $this->productService->getProductVariations( $event->product )->each( function( $variation ) {
            /**
             * deleting a variation
             * could also trigger the same product event
             * and it could be cached or tweaked
             */
            $this->productService->deleteVariations( $variation );
        });
    }
}
