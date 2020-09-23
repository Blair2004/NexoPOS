<{{ '?php' }}
namespace Modules\{{ $module[ 'namespace' ] }};

use Illuminate\Support\Facades\Event;
use App\Services\Module;

class {{ $module[ 'namespace' ] }}Module extends Module
{
    public function __construct()
    {
        parent::__construct( __FILE__ );
    }
}