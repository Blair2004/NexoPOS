<?php

namespace App\Events;

use App\Models\CashFlow;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashFlowHistoryBeforeDeleteEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $cashFlow;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct( CashFlow $cashFlow )
    {
        $this->cashFlow   =   $cashFlow;
    }
}
