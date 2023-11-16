<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CashRegistersService;
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
    public function handle( CashRegistersService $cashRegistersService )
    {
        /**
         * If the payment status changed from
         * supported payment status to a "Paid" status.
         */
        if ( $this->order->register_id !== null && $this->order->payment_status === Order::PAYMENT_PAID ) {
            $cashRegistersService->recordCashRegisterHistorySale(
                order: $this->order
            );
        }
    }
}
