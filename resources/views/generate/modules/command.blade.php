<{{ '?php' }}
/**
 * {{ $module[ 'name' ] }} Command
 * @since {{ $module[ 'version' ] }}
 * @package modules/{{ $module[ 'namespace' ] }}
**/

namespace Modules\{{ $module[ 'namespace' ] }}\Console\Commands;

use Illuminate\Console\Command;

class {{ $name }} extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Describe what does the command.';

    public function handle()
    {
        return 0;
    }
}
