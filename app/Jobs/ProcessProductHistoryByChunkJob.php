<?php

namespace App\Jobs;

use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProductHistoryByChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct( protected $products, protected $dayPeriod = 'start_of_day' )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle( ReportService $reportService ): void
    {
        if ( $this->dayPeriod === 'start_of_day' ) {
            $reportService->generateStartOfDayDetailedHistory(
                products: $this->products
            );
        } else if ( $this->dayPeriod === 'end_of_day' ) {
            $reportService->generateEndOfDayDetailedHistory(
                products: $this->products
            );
        }
    }
}
