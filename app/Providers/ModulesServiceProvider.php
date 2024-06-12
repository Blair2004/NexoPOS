<?php

namespace App\Providers;

use App\Events\ModulesBootedEvent;
use App\Events\ModulesLoadedEvent;
use App\Services\Helper;
use App\Services\ModulesService;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    protected $modulesCommands = [];

    protected ModulesService $modules;

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
        collect( $modules->getEnabledAndAutoloadedModules() )
            ->each( function ( $module ) use ( $modules ) {
                $modules->triggerServiceProviders( $module, 'boot', ServiceProvider::class );
            } );

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
        $this->app->singleton( ModulesService::class, function ( $app ) {
            $this->modules = new ModulesService;

            if ( Helper::installed() ) {
                $this->modules->load();

                /**
                 * We want to make sure all modules are loaded, before
                 * we can trigger the migrations. As some migrations may
                 * have a dependency on another module.
                 */
                $this->modules->loadModulesMigrations();

                $this->modules->getEnabledAndAutoloadedModules()->each( fn( $module ) => $this->modules->boot( $module ) );

                /**
                 * trigger register method only for enabled modules
                 * service providers that extends ModulesServiceProvider.
                 */
                $this->modules->getEnabledAndAutoloadedModules()->each( function ( $module ) {
                    /**
                     * register module commands
                     */
                    $this->modulesCommands = array_merge(
                        $this->modulesCommands,
                        array_keys( $module[ 'commands' ] )
                    );

                    $this->modules->triggerServiceProviders( $module, 'register', ServiceProvider::class );
                } );

                event( new ModulesLoadedEvent( $this->modules->get() ) );
            }

            return $this->modules;
        } );
    }
}
