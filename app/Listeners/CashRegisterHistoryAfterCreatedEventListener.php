<?php

namespace App\Listeners;

use App\Events\CashRegisterHistoryAfterCreatedEvent;
use App\Jobs\RecordCashRegisterHistoryOnTransactionsJob;
use App\Jobs\UpdateCashRegisterBalanceFromHistoryJob;

class CashRegisterHistoryAfterCreatedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( CashRegisterHistoryAfterCreatedEvent $event ): void
    {
        RecordCashRegisterHistoryOnTransactionsJob::dispatch( $event->registerHistory );
        UpdateCashRegisterBalanceFromHistoryJob::dispatch( $event->registerHistory );
    }
}
