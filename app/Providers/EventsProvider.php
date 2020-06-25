<?php
namespace App\Providers;

use Tendoo\Core\Facades\Hook;
use App\Events\CrudEvent;
use App\Events\ValidationEvent;
use App\Listeners\ProcurementListener;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Listeners\ProductListener;

class EventsProvider extends ServiceProvider
{
    protected $subscribe    =   [
        ProcurementListener::class,
        ProductListener::class,
    ];

    public function register()
    {
        Hook::addFilter( 'nexopos.units-groups.validation', useThis( ValidationEvent::class )->method( 'unitsGroups' ) );
        Hook::addFilter( 'nexopos.units.validation', useThis( ValidationEvent::class )->method( 'unitValidation' ) );
        Hook::addFilter( 'nexopos.procurements.validation', useThis( ValidationEvent::class )->method( 'procurementValidation' ) );
        Hook::addFilter( 'register.crud', useThis( CrudEvent::class )->method( 'register' ) );
        Hook::addFilter( 'dashboard.crud.validation', useThis( CrudEvent::class )->method( 'validation' ), 10, 3 );
    }
}