<?php

namespace App\Events;

use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\RewardSystem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CustomerRewardAfterCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reward;
    public $customer;
    public $customerReward;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( CustomerReward $customerReward, Customer $customer, RewardSystem $reward )
    {
        $this->customerReward   =   $customerReward;
        $this->customer         =   $customer;
        $this->reward           =   $reward;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('ns.private-channel');
    }
}
