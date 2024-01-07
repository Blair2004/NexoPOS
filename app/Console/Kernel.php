<?php

namespace App\Console;

use App\Jobs\ClearHoldOrdersJob;
use App\Jobs\ClearModuleTempJob;
use App\Jobs\DetectLowStockProductsJob;
use App\Jobs\DetectScheduledTransactionsJob;
use App\Jobs\EnsureCombinedProductHistoryExistsJob;
use App\Jobs\ExecuteReccuringTransactionsJob;
use App\Jobs\PurgeOrderStorageJob;
use App\Jobs\StockProcurementJob;
use App\Jobs\TrackLaidAwayOrdersJob;
use App\Services\ModulesService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;

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
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        /**
         * @todo ensures some jobs can also be executed on multistore.
         * This could be made through events that are dispatched within
         * the jobs
         */
        $schedule->call(function () {
            if (env('TELESCOPE_ENABLED', false)) {
                Artisan::call('telescope:prune', [ 'hours' => 12 ]);
            }
        })->daily();

        /**
         * This will check if cron jobs are correctly configured
         * and delete the generated notification if it was disabled.
         */
        $schedule->call(fn() => ns()->setLastCronActivity())->everyMinute();

        /**
         * This will check every minutes if the symbolic link is
         * broken to the storage folder.
         */
        $schedule->call(fn() => ns()->checkSymbolicLinks())->hourly();

        /**
         * Will execute transactions job daily.
         */
        $schedule->job(new ExecuteReccuringTransactionsJob)->daily('00:01');

        /**
         * Will execute scheduled transactions
         * every minutes
         */
        $schedule->job(DetectScheduledTransactionsJob::class)->everyFiveMinutes();

        /**
         * Will check procurement awaiting automatic
         * stocking to update their status.
         */
        $schedule->job(new StockProcurementJob)->daily('00:05');

        /**
         * Will purge stoarge orders daily.
         */
        $schedule->job(new PurgeOrderStorageJob)->daily('15:00');

        /**
         * Will clear hold orders that has expired.
         */
        $schedule->job(new ClearHoldOrdersJob)->dailyAt('14:00');

        /**
         * Will detect products that has reached the threashold of
         * low inventory to trigger a notification and an event.
         */
        $schedule->job(new DetectLowStockProductsJob)->dailyAt('00:02');

        /**
         * Will track orders saved with instalment and
         * trigger relevant notifications.
         */
        $schedule->job(new TrackLaidAwayOrdersJob)->dailyAt('13:00');

        /**
         * We'll check if there is a ProductHistoryCombined that was generated
         * during the current day. If it's not the case, we'll create one.
         */
        $schedule->job(new EnsureCombinedProductHistoryExistsJob)->hourly();

        /**
         * We'll clear temporary files weekly. This will erase folder that
         * hasn't been deleted after a module installation.
         */
        $schedule->job(new ClearModuleTempJob)->weekly();

        /**
         * @var ModulesService $modules
         */
        $modules = app()->make(ModulesService::class);

        /**
         * We want to make sure Modules Kernel get injected
         * on the process so that modules jobs can also be scheduled.
         */
        collect($modules->getEnabled())->each(function ($module) use ($schedule) {
            $filePath = $module[ 'path' ] . 'Console' . DIRECTORY_SEPARATOR . 'Kernel.php';

            if (is_file($filePath)) {
                include_once $filePath;

                $kernelClass = 'Modules\\' . $module[ 'namespace' ] . '\Console\Kernel';

                /**
                 * a kernel class should be defined
                 * on the module before it's initialized.
                 */
                if (class_exists($kernelClass)) {
                    $object = new $kernelClass($this->app, $this->events);
                    $object->schedule($schedule);
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
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
