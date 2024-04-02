<?php

namespace App\Jobs;

use App\Models\Role;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

class RecomputeCashFlowForDate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public $fromDate, public $toDate )
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( ReportService $reportService )
    {
        $wasLoggedIn = true;

        if ( ! Auth::check() ) {
            $wasLoggedIn = false;
            $user = Role::namespace( 'admin' )->users->first();
            Auth::login( $user );
        }

        $this->fromDate = Carbon::parse( $this->fromDate );
        $this->toDate = Carbon::parse( $this->toDate );

        $reportService->recomputeTransactions( $this->fromDate, $this->toDate );

        if ( ! $wasLoggedIn ) {
            Auth::logout();
        }
    }
}
