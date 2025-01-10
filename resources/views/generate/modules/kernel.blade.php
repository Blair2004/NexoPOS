<{{ '?php' }}
namespace Modules\{{ $module[ 'namespace' ] }}\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Register Kernel
 **/
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule( Schedule $schedule )
    {
        // ...
    }
}