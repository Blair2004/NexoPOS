<?php

namespace App\Jobs;

use App\Events\ProductAfterStockAdjustmentEvent;
use App\Models\ProductHistory;
use App\Services\ReportService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleStockAdjustmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NsSerialize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public ProductHistory $history )
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( ReportService $reportService )
    {
        $reportService->handleStockAdjustment( $this->history );
    }
}
