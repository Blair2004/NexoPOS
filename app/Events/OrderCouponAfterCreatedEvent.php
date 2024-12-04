<?php

namespace App\Events;

use App\Models\OrderCoupon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCouponAfterCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( public OrderCoupon $orderCoupon )
    {
        $this->orderCoupon->load( 'order' );
    }
}
