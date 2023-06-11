<{{ '?php' }}
namespace Modules\{{ $module[ 'namespace' ] }}\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Register Job
**/
class {{ $name }} implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        // ...
    }

    /**
     * ...
     * @return void
     */
    public function handle()
    {
        // ...
    }
}