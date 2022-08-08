<?php

namespace App\Listeners;

use App\Events\OrderAfterPaymentCreatedEvent;
use App\Jobs\ComputeDayReportJob;
use App\Jobs\ExpenseHandlePaymentStatusJob;
use App\Jobs\ProcessCashRegisterHistoryFromPaymentJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class OrderPaymentAfterCreatedEventListener
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
    public function handle( OrderAfterPaymentCreatedEvent $event )
    {
        ComputeDayReportJob::dispatch();
        ProcessCashRegisterHistoryFromPaymentJob::dispatch( $event->order, $event->orderPayment );
    }
}
