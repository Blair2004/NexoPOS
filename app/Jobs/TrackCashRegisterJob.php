<?php

namespace App\Jobs;

use App\Models\OrderPayment;
use App\Services\CashRegistersService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrackCashRegisterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct( public OrderPayment $orderPayment )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle( CashRegistersService $cashRegistersService ): void
    {
        $cashRegistersService->recordOrderPayment( $this->orderPayment );
    }
}
