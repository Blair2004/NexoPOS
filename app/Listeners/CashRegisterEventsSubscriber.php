<?php

namespace App\Listeners;

use App\Events\CashRegisterHistoryAfterCreatedEvent;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterPaymentCreatedEvent;
use App\Events\OrderAfterUpdatedEvent;
use App\Services\CashRegistersService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CashRegisterEventsSubscriber
{
    public $registerService;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        CashRegistersService $registerService
    )
    {
        $this->registerService      =       $registerService;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //
    }

    public function subscribe( $event )
    {
        $event->listen( 
            CashRegisterHistoryAfterCreatedEvent::class, 
            [ $this->registerService, 'updateRegisterAmount' ]
        );

        $event->listen( 
            CashRegisterHistoryAfterCreatedEvent::class, 
            [ $this->registerService, 'issueExpenses' ]
        );

        $event->listen( 
            OrderAfterPaymentCreatedEvent::class,
            [ $this->registerService, 'increaseFromOrderPayment' ]
        );

        $event->listen(
            OrderRefundPaymentAfterCreatedEvent::class,
            [ $this->registerService, 'afterOrderRefunded' ]
        );
    }
}
