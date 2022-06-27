<?php

namespace App\Providers;

use App\Classes\Hook;
use App\Filters\MenusFilter;
use App\Listeners\CashRegisterEventsSubscriber;
use App\Listeners\CustomerEventSubscriber;
use App\Listeners\ExpensesEventSubscriber;
use App\Listeners\OrderEventsSubscriber;
use App\Listeners\ProcurementEventsSubscriber;
use App\Listeners\ProductEventsSubscriber;
use App\Services\ModulesService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    protected $subscribe = [];

    public function register()
    {
        parent::register();
    }

    /**
     * Get the listener directories that should be used to discover events.
     *
     * @return array
     */
    protected function discoverEventsWithin()
    {
        /**
         * @var ModulesService
         */
        $modulesServices = app()->make( ModulesService::class );

        $paths = collect( $modulesServices->getEnabled() )->map( function( $module ) {
            return base_path( 'modules' . DIRECTORY_SEPARATOR . $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Listeners' );
        })->values()->toArray();

        return $paths;
    }

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Event::subscribe( ProcurementEventsSubscriber::class );
        Event::subscribe( ProductEventsSubscriber::class );
        Event::subscribe( OrderEventsSubscriber::class );
        Event::subscribe( ExpensesEventSubscriber::class );
        Event::subscribe( CustomerEventSubscriber::class );
        Event::subscribe( CashRegisterEventsSubscriber::class );

        Hook::addFilter( 'ns-dashboard-menus', [ MenusFilter::class, 'injectRegisterMenus' ]);
    }

    public function shouldDiscoverEvents()
    {
        return true;
    }
}
