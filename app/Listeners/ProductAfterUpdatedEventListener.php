<?php

namespace App\Listeners;

use App\Events\ProductAfterUpdatedEvent;
use App\Jobs\ComputeCategoryProductsJob;
use App\Models\ProductCategory;
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
    public function handle( ProductAfterUpdatedEvent $event )
    {
        $this->productService->generateProductBarcode( $event->product );

        /**
         * We'll pull the category stored
         * and check if it's different from the new defined category.
         * This will help computing total items for the old category.
         */
        $oldCategoryId = session()->pull( 'product_category_id' );

        if ( $oldCategoryId !== $event->product->category->id ) {
            ComputeCategoryProductsJob::dispatch( ProductCategory::find( $oldCategoryId ) );
        }

        /**
         * We'll now compute the total items
         * for the newly defined category. If it's the same category,
         * then a new count will only be made.
         */
        ComputeCategoryProductsJob::dispatch( $event->product->category );
    }
}
