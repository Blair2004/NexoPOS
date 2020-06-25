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
            useThis( ProcurementListener::class )->method( 'procurementRefreshing' )
        );

        $events->listen(
            ProcurementCancelationEvent::class,
            useThis( ProcurementListener::class )->method( 'procurementRefreshing' )
        );

        $events->listen(
            ProcurementDeletionEvent::class,
            useThis( ProcurementListener::class )->method( 'deleteProcurementProducts' )
        );

        $events->listen(
            ProcurementProductSavedEvent::class,
            useThis( ProcurementListener::class )->method( 'recordProcurementHistory' )
        );

        $events->listen(
            ProcurementBeforeUpdateProduct::class,
            useThis( ProcurementListener::class )->method( 'cancelProductProcurement' )
        );

        $events->listen(
            ProcurementAfterUpdateProduct::class,
            useThis( ProcurementListener::class )->method( 'updateProductProcurement' )
        );

        $events->listen(
            ProcurementAfterUpdateProduct::class,
            useThis( ProcurementListener::class )->method( 'refreshProcurement' )
        );

        $events->listen(
            ProcurementBeforeDeleteProduct::class,
            useThis( ProcurementListener::class )->method( 'handleBeforeDeletingProduct' )
        );

        $events->listen(
            ProcurementAfterDeleteProduct::class,
            useThis( ProcurementListener::class )->method( 'handleAfterDeletingProduct' )
        );

        $events->listen(
            ProcurementAfterDelete::class,
            useThis( ProcurementListener::class )->method( 'handleAfterProcurementDeleted' )
        );

        $events->listen(
            ProcurementBeforeDelete::class,
            useThis( ProcurementListener::class )->method( 'handleBeforeProcurementDeleted' )
        );
    }

    public function procurementRefreshing( $event )
    {
        $this->procurementService->refresh( $event->procurement );
    }

    public function deleteProcurementProducts( $event )
    {
        $this->procurementService->deleteProducts( $event->procurement );
    }

    public function recordProcurementHistory( $event )
    {
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

    /**
     * this will helps to remove the
     * stock which has been previously 
     * provided on the product
     * @param ProcurementBeforeUpdateProduct
     * @return void
     */
    public function cancelProductProcurement( $event )
    {
        // 
    }

    /**
     * this will record a new procurement
     * product
     * @param ProcurementAfterUpdateProduct
     */
    public function updateProductProcurement( $event )
    {
        $this->productService->procurementStockEntry( $event->product, $event->fields );
    }

    /**
     * refresh a procurement when a product has
     * been edited
     * @param ProcurementAfterUpdateProduct
     */
    public function refreshProcurement( $event )
    {
        $this->procurementService->refresh( $event->product->procurement );
    }

    public function handleBeforeDeletingProduct( ProcurementBeforeDeleteProduct $event )
    {
        // ...
    }

    public function handleAfterDeletingProduct( ProcurementBeforeDeleteProduct $event )
    {
        $this->procurementService->refresh( $event->procurement_id );                
    }

    public function handleAfterProcurementDeleted( ProcurementAfterDelete $event ) 
    {
        // ...
    }

    public function handleBeforeProcurementDeleted( ProcurementBeforeDelete $event ) 
    {
        // ...
    }

}
