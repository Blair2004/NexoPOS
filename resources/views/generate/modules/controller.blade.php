<{{ '?php' }}

/**
 * {{ $module[ 'name' ] }} Controller
 * @since {{ $module[ 'version' ] }}
 * @package modules/{{ $module[ 'namespace' ] }}
**/

namespace Modules\{{ $module[ 'namespace' ] }}\Http\Controllers;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\DashboardController;

class {{ $name }} extends DashboardController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Index Controller Page
     * @return view
     * @since {{ $module[ 'version' ] }}
    **/
    public function index()
    {
        return $this->view( '{{ $module[ 'namespace' ] }}::index' );
    }
}
