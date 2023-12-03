<{{ '?php' }}

/**
 * {{ $module[ 'name' ] }} Controller
 * @since {{ $module[ 'version' ] }}
 * @package modules/{{ $module[ 'namespace' ] }}
**/

namespace Modules\{{ $module[ 'namespace' ] }}\Http\Controllers;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\Controller;

class {{ $name }} extends Controller
{
    /**
     * Main Page
     * @since {{ $module[ 'version' ] }}
    **/
    public function index()
    {
        return $this->view( '{{ $module[ 'namespace' ] }}::index' );
    }
}
