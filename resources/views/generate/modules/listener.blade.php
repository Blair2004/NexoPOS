<{{ '?php' }}

namespace Modules\{{ $module[ 'namespace' ] }}\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class {{ $name }}
{
    /**
     * Handle the event.
     */
    public function handle( $event )
    {
        //
    }
}
