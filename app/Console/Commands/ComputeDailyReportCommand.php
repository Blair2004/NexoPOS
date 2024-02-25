<?php

namespace App\Console\Commands;

use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ComputeDailyReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:report {--compute} {--from=} {--type=day}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Will compute the daily report from a specific day to the present moment (according to the server date)';

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
        if ( $this->option( 'compute' ) ) {
            if ( $this->option( 'type' ) === 'day' ) {
                $this->computeDayReport();
            } elseif ( $this->option( 'type' ) === 'month' ) {
                $this->computeMonthReport();
            }
        }
    }

    public function computeMonthReport()
    {
        $from = Carbon::parse( $this->option( 'from' ) );
        $monthCursor = $from->copy();
        $currentDate = ns()->date->getNow();
        $dates = collect( [] );

        while ( ! $monthCursor->isSameMonth( $currentDate->copy()->addMonth() ) ) {
            $dates->push( $monthCursor->copy() );
            $monthCursor->addMonth();
        }

        if ( $dates->count() === 0 ) {
            return $this->error( __( 'An invalid date were provided. Make sure it a prior date to the actual server date.' ) );
        }

        $this->info( sprintf(
            __( 'Computing report from %s...' ),
            $from->format( 'Y-m' ),
        ) );

        $this->newLine();

        /**
         * @var ReportService
         */
        $reportService = app()->make( ReportService::class );

        /**
         * let's show how it progresses
         */
        $this->withProgressBar( $dates, function ( $date ) use ( $reportService ) {
            $reportService->computeDashboardMonth( $date );
        } );

        $this->newLine();

        $this->info( __( 'The operation was successful.' ) );
    }

    public function computeDayReport()
    {
        $from = Carbon::parse( $this->option( 'from' ) );
        $dayCursor = $from->copy();
        $currentDate = ns()->date->getNow();
        $dates = collect( [] );

        while ( ! $dayCursor->isSameDay( $currentDate->copy()->addDay() ) ) {
            $dates->push( $dayCursor->copy() );
            $dayCursor->addDay();
        }

        if ( $dates->count() === 0 ) {
            return $this->error( __( 'An invalid date were provided. Make sure it a prior date to the actual server date.' ) );
        }

        $this->info( sprintf(
            __( 'Computing report from %s...' ),
            $from->format( 'Y-m-d' ),
        ) );

        $this->newLine();

        /**
         * @var ReportService
         */
        $reportService = app()->make( ReportService::class );

        /**
         * let's show how it progresses
         */
        $this->withProgressBar( $dates, function ( $date ) use ( $reportService ) {
            $reportService->computeDayReport(
                $date->startOfDay()->toDateTimeString(),
                $date->endOfDay()->toDateTimeString()
            );
        } );

        $this->newLine();

        $this->info( __( 'The operation was successful.' ) );
    }
}
