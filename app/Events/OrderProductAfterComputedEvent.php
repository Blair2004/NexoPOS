<?php

namespace App\Events;

use App\Models\OrderProduct;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderProductAfterComputedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $orderProduct;
    public $total_gross_discount;
    public $total_discount;
    public $total_net_discount;
    public $net_discount;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( OrderProduct $orderProduct, $total_gross_discount, $total_discount, $total_net_discount, $net_discount )
    {
        $this->orderProduct             =   $orderProduct;
        $this->total_gross_discount     =   $total_gross_discount;
        $this->total_discount           =   $total_discount;
        $this->total_net_discount       =   $total_net_discount;
        $this->net_discount       =   $net_discount;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
