<?php

namespace App\Listeners;

use App\Events\ProductHistoryAfterCreatedEvent;
use App\Services\ProductService;
use App\Services\ReportService;

class ProductHistoryAfterCreatedEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        protected ProductService $productService,
        protected ReportService $reportService,
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( ProductHistoryAfterCreatedEvent $event ): void
    {
        $this->productService->computeCogsIfNecessary( $event->productHistory );
        $this->reportService->combineProductHistory( $event->productHistory );
    }
}
