<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CashRegistersService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordOrderChangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct( public Order $order )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle( CashRegistersService $cashRegistersService ): void
    {
        if ( $this->order->payment_status === Order::PAYMENT_PAID ) {
            $cashRegistersService->saveOrderChange( $this->order );
        }
    }
}
