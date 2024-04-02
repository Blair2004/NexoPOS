<?php

namespace App\Jobs;

use App\Services\ProcurementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StockProcurementJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var ProcurementService
     */
    public $procurementService;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        // ...
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( ProcurementService $procurementService )
    {
        $procurementService->stockAwaitingProcurements();
    }
}
