<?php

namespace App\Providers;

use App\Classes\Hook;
use App\Filters\MenusFilter;
use App\Services\ModulesService;
use App\Services\OrdersService;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $subscribe = [];

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

        $paths = $modulesServices->getEnabledAndAutoloadedModules()->map( function ( $module ) {
                return base_path( 'modules' . DIRECTORY_SEPARATOR . $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Listeners' );
            } )
            ->values()
            ->toArray();

        return $paths;
    }

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Hook::addFilter( 'ns-dashboard-menus', [ MenusFilter::class, 'injectRegisterMenus' ] );
        Hook::addFilter( 'ns-common-routes', [ app()->make( OrdersService::class ), 'handlePOSRoute' ], 10, 3 );
    }
}
