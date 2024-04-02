<?php

namespace App\Listeners;

use App\Events\OrderAfterDeletedEvent;
use App\Jobs\RefreshReportJob;
use App\Jobs\UncountDeletedOrderForCashierJob;
use App\Jobs\UncountDeletedOrderForCustomerJob;
use App\Models\Order;
use App\Models\Register;
use App\Services\CashRegistersService;

class OrderAfterDeletedEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public CashRegistersService $cashRegistersService
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle( OrderAfterDeletedEvent $event )
    {
        UncountDeletedOrderForCashierJob::dispatch( $event->order );
        UncountDeletedOrderForCustomerJob::dispatch( $event->order );

        $register = Register::find( $event->order->register_id );

        if ( $register instanceof Register ) {
            if ( in_array( $event->order->payment_status, [ Order::PAYMENT_PAID, Order::PAYMENT_PARTIALLY ] ) ) {
                $this->cashRegistersService->saleDelete( $register, $event->order->total, __( 'The transaction was deleted.' ) );
            }

            if ( $event->order->payment_status === Order::PAYMENT_PARTIALLY ) {
                $this->cashRegistersService->saleDelete( $register, $event->order->tendered, __( 'The transaction was deleted.' ) );
            }
        }

        RefreshReportJob::dispatch( $event->order->updated_at );
    }
}
