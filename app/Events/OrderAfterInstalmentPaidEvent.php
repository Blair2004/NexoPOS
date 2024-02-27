<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderInstalment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderAfterInstalmentPaidEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( public OrderInstalment $instalment, public Order $order )
    {
        // ...
    }
}
