<{{ '?php' }}
namespace Modules\{{ $module[ 'namespace' ] }}\Events;

/**
 * Register Events
**/
class {{ $module[ 'namespace' ] }}Event
{
    public function __construct( Menus $menus )
    {
        $this->menus    =   $menus;
    }
}