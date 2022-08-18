<?php

namespace App\Jobs;

use App\Models\RegisterHistory;
use App\Services\CashRegistersService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateCashRegisterBalanceFromHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public RegisterHistory $registerHistory )
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
        $cashRegistersService->updateRegisterBalance( $this->registerHistory );
    }
}
