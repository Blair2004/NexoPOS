<?php

namespace App\Listeners;

use App\Events\OrderAfterProductRefundedEvent;
use App\Jobs\CreateExpenseFromRefundJob;
use App\Jobs\RefreshOrderJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
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
     * @param  \App\Events\OrderAfterProductRefundedEvent  $event
     * @return void
     */
    public function handle(OrderAfterProductRefundedEvent $event)
    {
        Bus::chain([
            new RefreshOrderJob( $event->order ),
            new CreateExpenseFromRefundJob(
                order: $event->order,
                orderProduct: $event->orderProduct,
                orderProductRefund: $event->orderProductRefund
            )
        ])->dispatch();
    }
}
