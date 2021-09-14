<?php
namespace App\Providers;

use App\Services\ModulesService;
use Illuminate\Support\ServiceProvider;
use App\Events\ModulesLoadedEvent;
use App\Events\ModulesBootedEvent;

class ModulesServiceProvider extends ServiceProvider
{
    protected $modulesCommands  =   [];
    protected $modules;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot( ModulesService $modules )
    {
        /**
         * trigger boot method only for enabled modules
         * service providers that extends ModulesServiceProvider.
         */
        collect( $modules->getEnabled() )->each( function( $module ) use ( $modules ) {
            $modules->triggerServiceProviders( $module, 'boot', ServiceProvider::class );
        });

        $this->commands( $this->modulesCommands );

        /**
         * trigger an event when all the module
         * has successfully booted.
         */
        ModulesBootedEvent::dispatch();
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
            $this->modules  =   new ModulesService;
            $this->modules->load();

            collect( $this->modules->getEnabled() )->each( fn( $module ) => $this->modules->boot( $module ) );
                
            /**
             * trigger register method only for enabled modules
             * service providers that extends ModulesServiceProvider.
             */
            collect( $this->modules->getEnabled() )->each( function( $module ) {
                /**
                 * register module commands
                 */
                $this->modulesCommands    =   array_merge(
                    $this->modulesCommands,
                    array_keys( $module[ 'commands' ] )
                );

                $this->modules->triggerServiceProviders( $module, 'register', ServiceProvider::class );
            });

            event( new ModulesLoadedEvent( $this->modules->get() ) );

            return $this->modules;
        });
    }
}
