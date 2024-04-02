<?php

namespace App\Jobs;

use App\Models\Order;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class IncreaseCashierStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Order $order )
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
        if ( $this->order->payment_status === Order::PAYMENT_PAID ) {
            $this->order->user->total_sales = $this->order->user->total_sales + $this->order->total;
            $this->order->user->total_sales_count = $this->order->user->total_sales_count + 1;
            $this->order->user->save();
        }
    }
}
