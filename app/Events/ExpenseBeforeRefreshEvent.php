<?php

namespace App\Events;

use App\Models\DashboardDay;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExpenseBeforeRefreshEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $dashboardDay;
    public $date;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( DashboardDay $dashboardDay, Carbon $date )
    {
        $this->dashboardDay     =   $dashboardDay;
        $this->date             =   $date;
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
