<?php
namespace App\Listeners;

use App\Services\ProductService;
use App\Events\ProductAfterDeleteEvent;
use App\Events\ProductBeforeDeleteEvent;
use App\Services\ProcurementService;

class ProductEventsSubscriber
{
    /** @var ProductService */
    protected $productService;

    /** @var ProcurementService */
    protected $procurementService;

    public function __construct(
        ProductService $productService,
        ProcurementService $procurementService
    ) {
        $this->productService       =   $productService;
        $this->procurementService   =   $procurementService;
    }

    public function subscribe( $events )
    {
        $events->listen(
            ProductBeforeDeleteEvent::class,
            useThis( ProductListener::class )->method( 'beforeDeleteProduct' )
        );
        
        $events->listen(
            ProductAfterDeleteEvent::class,
            useThis( ProductListener::class )->method( 'afterDeleteProduct' )
        );
    }

    public function beforeDeleteProduct( ProductBeforeDeleteEvent $event )
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

    public function afterDeleteProduct( ProductAfterDeleteEvent $event )
    {
    }
}