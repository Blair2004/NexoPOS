<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class GenerateTransactionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:transactions {--from=} {--to=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear and generate transactions for existing data.';

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
        $user = Role::namespace( 'admin' )->users->first();
        Auth::login( $user );

        $fromDate = Carbon::parse( $this->option( 'from' ) );
        $toDate = $this->option( 'to' ) === null ? Carbon::parse( $this->option( 'to' ) ) : ns()->date->copy()->endOfDay()->toDateTimeString();

        /**
         * @var ReportService $reportService
         */
        $reportService = app()->make( ReportService::class );
        $reportService->recomputeTransactions( $fromDate, $toDate );

        $this->info( 'The cash flow has been generated.' );
    }
}
