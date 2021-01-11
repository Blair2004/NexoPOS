<?php
namespace App\Providers;

use App\Services\ModulesService;
use Illuminate\Support\ServiceProvider;
use App\Events\ModulesLoadedEvent;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot( ModulesService $modules )
    {
        collect( $modules->getEnabled() )->each( fn( $module ) => $modules->boot( $module ) );
        
        /**
         * trigger register method only for enabled modules
         * service providers that extends ModulesServiceProvider.
         */
        collect( $modules->getEnabled() )->each( function( $module ) use ( $modules ) {
            $modules->triggerServiceProviders( $module, 'register', ServiceProvider::class );
        });

        /**
         * trigger boot method only for enabled modules
         * service providers that extends ModulesServiceProvider.
         */
        collect( $modules->getEnabled() )->each( function( $module ) use ( $modules ) {
            $modules->triggerServiceProviders( $module, 'boot', ServiceProvider::class );
        });
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        // register module singleton
        $this->app->singleton( ModulesService::class, function( $app ) {
            $modules    =   new ModulesService;
            $modules->load();

            event( new ModulesLoadedEvent( $modules->get() ) );

            return $modules;
        });
    }
}
