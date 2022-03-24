<?php

namespace App\Listeners;

use App\Events\CashRegisterHistoryAfterCreatedEvent;
use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterPaymentCreatedEvent;
use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Events\OrderAfterUpdatedEvent;
use App\Services\CashRegistersService;

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
        // ...
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
            OrderAfterPaymentStatusChangedEvent::class,
            [ $this->registerService, 'increaseFromPaidOrder' ]
        );

        $event->listen( 
            OrderAfterCreatedEvent::class,
            [ $this->registerService, 'increaseFromOrderCreatedEvent' ]
        );

        $event->listen( 
            OrderAfterUpdatedEvent::class,
            [ $this->registerService, 'increaseFromOrderCreatedEvent' ]
        );

        $event->listen(
            OrderRefundPaymentAfterCreatedEvent::class,
            [ $this->registerService, 'afterOrderRefunded' ]
        );
    }
}
