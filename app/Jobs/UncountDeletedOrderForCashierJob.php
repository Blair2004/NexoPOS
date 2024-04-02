<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class UncountDeletedOrderForCashierJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public $order )
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
            $user = User::find( $this->order->author );
            $user->total_sales = $user->total_sales - $this->order->total;
            $user->total_sales_count = $user->total_sales_count - 1;
            $user->save();
        }
    }
}
