<?php

namespace App\Listeners;

use App\Events\OrderAfterProductRefundedEvent;
use App\Jobs\CreateExpenseFromRefundJob;
use App\Jobs\RefreshOrderJob;
use Illuminate\Support\Facades\Bus;

class OrderAfterProductRefundedEventListener
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
    public function handle( OrderAfterProductRefundedEvent $event )
    {
        Bus::chain( [
            // new RefreshOrderJob($event->order), this already called on OrderAfterRefundEvent
            new CreateExpenseFromRefundJob(
                order: $event->order,
                orderProduct: $event->orderProduct,
                orderProductRefund: $event->orderProductRefund
            ),
        ] )->dispatch();
    }
}
