<?php

namespace App\Console;

use App\Jobs\ClearHoldOrdersJob;
use App\Jobs\ExecuteExpensesJob;
use App\Jobs\PurgeOrderStorageJob;
use App\Jobs\StockProcurementJob;
use App\Jobs\TaskSchedulingPingJob;
use App\Jobs\TrackLaidAwayOrdersJob;
use App\Services\ModulesService;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->job( new TaskSchedulingPingJob )->hourly();
        $schedule->job( new ExecuteExpensesJob )->daily( '00:01' );
        $schedule->job( new StockProcurementJob() )->daily( '00:05' );        
        $schedule->job( new PurgeOrderStorageJob )->daily( '15:00' );
        $schedule->job( new ClearHoldOrdersJob )->dailyAt( '14:00' );
        $schedule->job( new TrackLaidAwayOrdersJob )->dailyAt( '13:00' ); // we don't want all job to run daily at the same time

        $modules    =   app()->make( ModulesService::class );

        collect( $modules->getEnabled() )->each( function( $module ) use ( $schedule ) {
            $filePath   =   $module[ 'path' ] . 'Console' . DIRECTORY_SEPARATOR . 'Kernel.php';

            if ( is_file( $filePath ) ) {
                
                include_once( $filePath );
                
                $kernelClass    =   'Modules\\' . $module[ 'namespace' ] . '\Console\Kernel';

                /**
                 * a kernel class should be defined
                 * on the module before it's initialized.
                 */
                if ( class_exists( $kernelClass ) ) {
                    $object     =   new $kernelClass( $this->app, $this->events );
                    $object->schedule( $schedule ); 
                }
            }
        });
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
