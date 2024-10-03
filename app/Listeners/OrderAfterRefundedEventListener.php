<?php

namespace App\Listeners;

use App\Events\OrderAfterRefundedEvent;
use App\Jobs\DecreaseCustomerPurchasesJob;
use App\Jobs\ReduceCashierStatsFromRefundJob;
use App\Jobs\RefreshOrderJob;
use Illuminate\Support\Facades\Bus;

class OrderAfterRefundedEventListener
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
     *
     * @return void
     */
    public function handle( OrderAfterRefundedEvent $event )
    {
        Bus::chain( [
            new RefreshOrderJob( $event->order ),
            new ReduceCashierStatsFromRefundJob( $event->order, $event->orderRefund ),
            new DecreaseCustomerPurchasesJob( $event->order->customer, $event->orderRefund->total ),
        ] )->dispatch();
    }
}
