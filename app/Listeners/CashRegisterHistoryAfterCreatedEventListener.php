<?php

namespace App\Listeners;

use App\Events\CashRegisterHistoryAfterCreatedEvent;
use App\Jobs\RecordCashRegisterHistoryOnTransactionsJob;
use App\Jobs\UpdateCashRegisterBalanceFromHistoryJob;
use React\Dns\Model\Record;

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
        UpdateCashRegisterBalanceFromHistoryJob::dispatch( $event->registerHistory );
        RecordCashRegisterHistoryOnTransactionsJob::dispatch( $event->registerHistory );
    }
}
