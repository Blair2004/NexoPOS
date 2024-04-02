<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderRefund;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ReduceCashierStatsFromRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Order $order, public OrderRefund $orderRefund )
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->order->user->total_sales -= $this->orderRefund->total;
        $this->order->user->save();
    }
}
