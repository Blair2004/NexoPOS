<?php

namespace App\Events;

use App\Models\CustomerAccountHistory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AfterCustomerAccountHistoryCreatedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $customerAccount;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( CustomerAccountHistory $customerAccount )
    {
        $this->customerAccount  =   $customerAccount;
    }
}
