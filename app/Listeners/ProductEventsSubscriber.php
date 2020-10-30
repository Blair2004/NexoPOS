<?php
namespace App\Listeners;

use App\Services\ProductService;
use App\Events\ProductAfterDeleteEvent;
use App\Events\ProductAfterStockAdjustmentEvent;
use App\Events\ProductBeforeDeleteEvent;
use App\Jobs\HandleStockAdjustmentJob;
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

    public function __construct(
        ProductService $productService,
        ProcurementService $procurementService,
        ReportService $reportService
    ) {
        $this->productService       =   $productService;
        $this->procurementService   =   $procurementService;
        $this->reportService        =   $reportService;
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

    public function afterStockAdjustment( ProductAfterStockAdjustmentEvent $event )
    {
        HandleStockAdjustmentJob::dispatch( $event )
            ->delay( now() );
    }

    public function afterDeleteProduct( ProductAfterDeleteEvent $event )
    {
    }
}