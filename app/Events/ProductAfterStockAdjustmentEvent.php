<?php

namespace App\Events;

use App\Models\ProductHistory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductAfterStockAdjustmentEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var ProductHistory
     */
    public $history;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( ProductHistory $history )
    {
        $this->history  =   $history;
    }
}
