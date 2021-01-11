<?php

namespace App\Jobs;

use App\Events\ProductAfterStockAdjustmentEvent;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class HandleStockAdjustmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $history;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( ProductAfterStockAdjustmentEvent $event )
    {
        $this->history  =   $event->history;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * @var ReportService
         */
        $reportService     =   app()->make( ReportService::class );
        $reportService->handleStockAdjustment( $this->history );
    }
}
