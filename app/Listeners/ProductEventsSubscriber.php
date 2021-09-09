<?php
namespace App\Listeners;

use App\Events\ProductAfterCreatedEvent;
use App\Services\ProductService;
use App\Events\ProductAfterDeleteEvent;
use App\Events\ProductAfterStockAdjustmentEvent;
use App\Events\ProductAfterUpdatedEvent;
use App\Events\ProductBeforeDeleteEvent;
use App\Jobs\HandleStockAdjustmentJob;
use App\Services\BarcodeService;
use App\Services\ProcurementService;
use App\Services\ReportService;

class ProductEventsSubscriber
{
    /** @var ProductService */
    protected $productService;

    /** @var ProcurementService */
    protected $procurementService;

    /** @var ReportService */
    protected $reportService;

    /** @var BarcodeService */
    protected $barcodeService;

    public function __construct(
        ProductService $productService,
        ProcurementService $procurementService,
        ReportService $reportService,
        BarcodeService $barcodeService
    ) {
        $this->productService       =   $productService;
        $this->procurementService   =   $procurementService;
        $this->reportService        =   $reportService;
        $this->barcodeService       =   $barcodeService;
    }

    public function subscribe( $events )
    {
        $events->listen(
            ProductBeforeDeleteEvent::class,
            [ ProductEventsSubscriber::class,  'beforeDeleteProduct' ]
        );
        
        $events->listen(
            ProductAfterDeleteEvent::class,
            [ ProductEventsSubscriber::class,  'afterDeleteProduct' ]
        );

        $events->listen(
            ProductAfterStockAdjustmentEvent::class,
            [ ProductEventsSubscriber::class, 'afterStockAdjustment' ]
        );

        $events->listen(
            ProductAfterCreatedEvent::class,
            [ ProductEventsSubscriber::class, 'generateBarcode' ]
        );

        $events->listen(
            ProductAfterUpdatedEvent::class,
            [ ProductEventsSubscriber::class, 'generateBarcode' ]
        );

        $events->listen(
            ProductAfterCreatedEvent::class,
            [ ProductEventsSubscriber::class, 'updateCategoryProduct' ]
        );

        $events->listen( 
            ProductBeforeDeleteEvent::class,
            [ ProductEventsSubscriber::class, 'deductCategoryProducts' ]
        );
    }

    public function deductCategoryProducts( ProductBeforeDeleteEvent $event )
    {
        $event->product->category->total_items--;
        $event->product->category->save();
    }

    public function updateCategoryProduct( ProductAfterCreatedEvent $event )
    {
        $event->product->category->total_items++;
        $event->product->category->save();
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

    public function generateBarcode( $event )
    {
        $this->barcodeService->generateBarcode(
            $event->product->barcode,
            $event->product->barcode_type
        );

        /**
         * save barcode for unit quantities
         */
        $event->product->unit_quantities->each( function( $unitQuantity ) use ( $event ) {
            $this->barcodeService->generateBarcode(
                $unitQuantity->barcode,
                $event->product->barcode_type
            );
        });
    }

    public function afterStockAdjustment( ProductAfterStockAdjustmentEvent $event )
    {
        HandleStockAdjustmentJob::dispatch( $event )
            ->delay( now() );
    }

    public function afterDeleteProduct( ProductAfterDeleteEvent $event )
    {
        // ...
    }
}