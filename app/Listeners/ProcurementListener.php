<?php
namespace App\Listeners;

use App\Services\ProductService;
use App\Services\ProcurementService;
use App\Events\ProcurementDeletionEvent;
use App\Events\ProcurementDeliveryEvent;
use App\Events\ProcurementCancelationEvent;
use App\Events\ProcurementProductSavedEvent;
use App\Events\ProcurementAfterUpdateProduct;
use App\Events\ProcurementBeforeDeleteProduct;
use App\Events\ProcurementBeforeUpdateProduct;
use App\Events\ProcurementRefreshedEvent;
use App\Services\ProviderService;

class ProcurementListener
{
    protected $procurementService;

    public function __construct( 
        ProcurementService $procurementService,
        ProductService $productService
    )
    {
        $this->procurementService   =   $procurementService;
        $this->productService       =   $productService;
    }

    public function subscribe( $events )
    {
        $events->listen(
            ProcurementDeliveryEvent::class,
            fn( $event ) => $this->procurementService->refresh( $event->procurement )
        );

        $events->listen(
            ProcurementRefreshedEvent::class,
            fn( $event ) => app()->make( ProviderService::class )->refreshFromProcurement( $event->procurement )
        );

        $events->listen(
            ProcurementCancelationEvent::class,
            fn( $event ) => $this->procurementService->refresh( $event->procurement )
        );

        $events->listen(
            ProcurementDeletionEvent::class,
            fn( $event ) => $this->procurementService->deleteProducts( $event->procurement )
        );

        $events->listen(
            ProcurementProductSavedEvent::class,
            function( $event ) {
                $this->productService->saveHistory( 'procurement', [
                    'product_id'                =>  $event->product->product_id,
                    'purchase_price'            =>  $event->product->purchase_price,
                    'total_price'               =>  $event->product->total_price,
                    'quantity'                  =>  $event->product->quantity,
                    'unit_id'                   =>  $event->product->unit_id,
                    'procurement_id'            =>  $event->product->procurement->id,
                    'procurement_product_id'    =>  $event->product->id
                ]);
            }
        );

        /**
         * this will helps to remove the
         * stock which has been previously 
         * provided on the product
         * @param ProcurementBeforeUpdateProduct
         * @return void
         */
        $events->listen(
            ProcurementBeforeUpdateProduct::class,
            fn( $event ) => null
        );

        $events->listen(
            ProcurementAfterUpdateProduct::class,
            fn( $event ) => $this->productService->procurementStockEntry( $event->product, $event->fields )
        );

        $events->listen(
            ProcurementAfterUpdateProduct::class,
            fn( $event ) => $this->procurementService->refresh( $event->product->procurement )
        );

        $events->listen(
            ProcurementBeforeDeleteProduct::class,
            fn( $event ) => null
        );

        $events->listen(
            ProcurementAfterDeleteProduct::class,
            fn( $event ) => $this->procurementService->refresh( $event->procurement_id )
        );

        $events->listen(
            ProcurementAfterDelete::class,
            fn( $event ) => null
        );

        $events->listen(
            ProcurementBeforeDelete::class,
            fn( $event ) => null
        );
    }
}
