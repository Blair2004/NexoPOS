<{{ '?php' }}
namespace Modules\{{ $module[ 'namespace' ] }};

use Illuminate\Support\Facades\Event;
use App\Services\TendooModule;

class {{ $module[ 'namespace' ] }}Module extends TendooModule
{
    public function __construct()
    {
        parent::__construct( __FILE__ );

        /**
         * Register Menus
        **/
        // Event::listen( 'dashboard.loaded', 'Modules\{{ $module[ 'namespace' ] }}\Events\{{ $module[ 'namespace' ] }}Events@menus' );
    }
}