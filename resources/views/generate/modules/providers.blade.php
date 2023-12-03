<{{ '?php' }}
/**
 * Service Provider
 * @package : {{ $module[ 'namespace' ] }}
**/
namespace Modules\{{ $module[ 'namespace' ] }}\Providers;
use Illuminate\Support\ServiceProvider as CoreServiceProvider;

class {{ $className }} extends CoreServiceProvider
{
    /**
     * register method
     */
    public function register()
    {
        // register stuff here
    }
    
    /**
     * Boot method
    **/
    public function boot()
    {
        // boot stuff here
    }
}