<?php

namespace App\Console\Commands;

use App\Models\CashFlow;
use App\Models\DashboardDay;
use App\Models\DashboardMonth;
use App\Models\Role;
use App\Models\User;
use App\Services\ExpenseService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class GenerateCashFlowCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:cash-flow {--from=} {--to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear and generate cash flow for existing data.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $fromDate   =   Carbon::parse( $this->option( 'from') );
        $toDate     =   $this->option( 'to' ) === null ? Carbon::parse( $this->option( 'to' ) ) : ns()->date->copy()->endOfDay()->toDateTimeString();
        
        CashFlow::truncate();
        DashboardDay::truncate();
        DashboardMonth::truncate();

        $user               =   Role::namespace( 'admin' )->users->first();
        $startDateString    =   $fromDate->startOfDay()->toDateTimeString();
        $endDateString      =   $toDate->endOfDay()->toDateTimeString();
        
        Auth::login( $user );

        /**
         * @var ExpenseService
         */
        $expenseService     =   app()->make( ExpenseService::class );
        
        $expenseService->recomputeCashFlow( 
            $startDateString, 
            $endDateString
        );

        /**
         * recompute dashboard reports
         * @var ReportService
         */
        $reportService      =   app()->make( ReportService::class );

        $days       =   ns()->date->getDaysInBetween( $fromDate, $toDate );

        foreach( $days as $day ) {
            $reportService->computeDayReport( 
                $day->startOfDay()->toDateTimeString(), 
                $day->endOfDay()->toDateTimeString()
            );

            $reportService->computeDashboardMonth( $day );
        }
        
        $this->info( 'The cash flow has been generated.' );
    }
}
