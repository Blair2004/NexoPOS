<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CashRegistersService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * @deprecated
 */
class ProcessCashRegisterHistoryJob implements ShouldQueue
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
    public function handle( CashRegistersService $cashRegistersService )
    {
        /**
         * If the payment status changed from
         * supported payment status to a "Paid" status.
         */
        if ( $this->order->register_id !== null ) {
            $cashRegistersService->recordCashRegisterHistorySale(
                order: $this->order
            );
        }
    }
}
