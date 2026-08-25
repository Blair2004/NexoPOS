<?php

namespace App\Listeners;

use App\Events\ProductAfterStockAdjustmentEvent;
use App\Jobs\HandleStockAdjustmentJob;
use App\Services\AccountingJournalService;

class ProductAfterStockAdjustmentEventListener
{
    public function __construct( private AccountingJournalService $accountingJournalService ) {}

    public function handle( ProductAfterStockAdjustmentEvent $event ): void
    {
        $this->accountingJournalService->postStockAdjustment( $event->history );
        HandleStockAdjustmentJob::dispatch( $event->history );
    }
}
