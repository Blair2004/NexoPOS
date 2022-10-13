<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\CashRegistersService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * @deprecated
 */
class ProcessCashRegisterHistoryFromPaymentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, NsSerialize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Order $order, public OrderPayment $orderPayment )
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
        // ...
    }
}
