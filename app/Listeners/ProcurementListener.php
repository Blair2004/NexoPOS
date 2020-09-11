<?php
namespace App\Listeners;

use App\Events\ProcurementAfterDeleteEvent;
use App\Services\ProductService;
use App\Services\ProcurementService;
use App\Events\ProcurementDeletionEvent;
use App\Events\ProcurementDeliveryEvent;
use App\Events\ProcurementCancelationEvent;
use App\Events\ProcurementProductSavedEvent;
use App\Events\ProcurementBeforeDeleteEvent;
use App\Events\ProcurementBeforeUpdateProductEvent;
use App\Events\ProcurementRefreshedEvent;
use App\Models\Provider;
use App\Services\ProviderService;

class ProcurementListener
{
    protected $procurementService;
    protected $providerService;
    protected $productService;

    public function __construct( 
        ProcurementService $procurementService,
        ProductService $productService,
        ProviderService $providerService
    )
    {
        $this->procurementService   =   $procurementService;
        $this->providerService     =   $providerService;
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
         * @param ProcurementBeforeUpdateProductEvent
         * @return void
         */
        $events->listen(
            ProcurementBeforeUpdateProductEvent::class,
            fn( $event ) => null
        );

        $events->listen(
            ProcurementAfterUpdateProductEvent::class,
            fn( $event ) => $this->productService->procurementStockEntry( $event->product, $event->fields )
        );

        $events->listen(
            ProcurementAfterUpdateProductEvent::class,
            fn( $event ) => $this->procurementService->refresh( $event->product->procurement )
        );

        $events->listen(
            ProcurementBeforeDeleteProductEvent::class,
            fn( $event ) => null
        );

        $events->listen(
            ProcurementAfterDeleteProductEvent::class,
            fn( $event ) => $this->procurementService->refresh( $event->procurement_id )
        );

        $events->listen(
            ProcurementAfterDeleteEvent::class,
            fn( $event ) => $this->providerService->computeSummary( 
                Provider::find( $event->procurement_data[ 'provider_id' ] ) 
            )
        );

        $events->listen(
            ProcurementBeforeDeleteEvent::class,
            fn( $event ) => $this->procurementService->attemptProductsStockRemoval( $event->procurement ),
        );

        $events->listen(
            ProcurementBeforeDeleteEvent::class,
            fn( $event ) => $this->procurementService->deleteProcurementProducts( $event->procurement ),
        );
    }
}
