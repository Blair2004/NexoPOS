<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class InitializeDailyDayReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public ReportService $reportService
    ) {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * We use the user as it's the first
         * created entity on the system
         */
        $user = User::first();
        $date = Carbon::parse( $user->created_at )->endOfDay();

        while ( ! $date->notEqualTo( ns()->date->endOfDay() ) ) {
            $this->reportService->computeDayReport(
                $date->copy()->startOfday()->toDateTimeString(),
                $date->copy()->endOfDay()->toDateTimeString()
            );

            $date->addDay();
        }
    }
}
