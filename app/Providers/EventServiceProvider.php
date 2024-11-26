<?php

namespace App\Providers;

use App\Classes\Hook;
use App\Filters\MenusFilter;
use App\Services\ModulesService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

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

        $paths = $modulesServices->getEnabledAndAutoloadedModules()->map( function ( $module ) {
            return base_path( 'modules' . DIRECTORY_SEPARATOR . $module[ 'namespace' ] . DIRECTORY_SEPARATOR . 'Listeners' );
        } )
            ->values()
            ->push( $this->app->path( 'Listeners' ) )
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
    }

    public function shouldDiscoverEvents()
    {
        return true;
    }
}
