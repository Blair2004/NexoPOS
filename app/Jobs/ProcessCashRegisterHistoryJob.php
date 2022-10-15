<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Register;
use App\Models\RegisterHistory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCashRegisterHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Order $order )
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * If the payment status changed from
         * supported payment status to a "Paid" status.
         */
        if ( $this->order->register_id !== null && $this->order->payment_status === Order::PAYMENT_PAID ) {
            $register = Register::find( $this->order->register_id );

            $registerHistory = new RegisterHistory;
            $registerHistory->balance_before = $register->balance;
            $registerHistory->value = $this->order->total;
            $registerHistory->balance_after = $register->balance + $this->order->total;
            $registerHistory->register_id = $this->order->register_id;
            $registerHistory->action = RegisterHistory::ACTION_SALE;
            $registerHistory->author = $this->order->author;
            $registerHistory->save();
        }
    }
}
