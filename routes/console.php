<?php

use App\Jobs\ClearHoldOrdersJob;
use App\Jobs\ClearModuleTempJob;
use App\Jobs\DetectLowStockProductsJob;
use App\Jobs\DetectScheduledTransactionsJob;
use App\Jobs\EnsureCombinedProductHistoryExistsJob;
use App\Jobs\ExecuteReccuringTransactionsJob;
use App\Jobs\PurgeOrderStorageJob;
use App\Jobs\StockProcurementJob;
use App\Jobs\TrackLaidAwayOrdersJob;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

/**
 * @todo ensures some jobs can also be executed on multistore.
 * This could be made through events that are dispatched within
 * the jobs
 */
Schedule::call( function () {
    if ( env( 'TELESCOPE_ENABLED', false ) ) {
        Artisan::call( 'telescope:prune', [ 'hours' => 12 ] );
    }
} )->daily();

/**
 * This will check if cron jobs are correctly configured
 * and delete the generated notification if it was disabled.
 */
Schedule::call( fn() => ns()->setLastCronActivity() )->everyMinute();

/**
 * This will check every minutes if the symbolic link is
 * broken to the storage folder.
 */
Schedule::call( fn() => ns()->checkSymbolicLinks() )->hourly();

/**
 * Will execute transactions job daily.
 */
Schedule::job( new ExecuteReccuringTransactionsJob )->daily( '00:01' );

/**
 * Will execute scheduled transactions
 * every minutes
 */
Schedule::job( DetectScheduledTransactionsJob::class )->everyFiveMinutes();

/**
 * Will check procurement awaiting automatic
 * stocking to update their status.
 */
Schedule::job( new StockProcurementJob )->daily( '00:05' );

/**
 * Will purge stoarge orders daily.
 */
Schedule::job( new PurgeOrderStorageJob )->daily( '15:00' );

/**
 * Will clear hold orders that has expired.
 */
Schedule::job( new ClearHoldOrdersJob )->dailyAt( '14:00' );

/**
 * Will detect products that has reached the threashold of
 * low inventory to trigger a notification and an event.
 */
Schedule::job( new DetectLowStockProductsJob )->dailyAt( '00:02' );

/**
 * Will track orders saved with instalment and
 * trigger relevant notifications.
 */
Schedule::job( new TrackLaidAwayOrdersJob )->dailyAt( '13:00' );

/**
 * We'll check if there is a ProductHistoryCombined that was generated
 * during the current day. If it's not the case, we'll create one.
 */
Schedule::job( new EnsureCombinedProductHistoryExistsJob )->hourly();

/**
 * We'll clear temporary files weekly. This will erase folder that
 * hasn't been deleted after a module installation.
 */
Schedule::job( new ClearModuleTempJob )->weekly();
