<{{ '?php' }}

/**
 * {{ $module[ 'name' ] }} Controller
 * @since {{ $module[ 'version' ] }}
**/

namespace Modules\{{ $module[ 'namespace' ] }}\Http\Controllers;
use Illuminate\Support\Facades\View;
use App\Http\Controllers\DashboardController;
// use Tendoo\Core\Services\Page;

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
        Page::setTitle( __( 'Unammed Page' ) );
        return View::make( '{{ $module[ 'namespace' ] }}::index' );
    }
}
