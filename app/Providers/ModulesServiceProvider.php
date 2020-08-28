<?php
namespace App\Providers;

use App\Services\ModulesService;
use Illuminate\Support\ServiceProvider;

class ModulesServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot( ModulesService $modules )
    {
        $modules->init();
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
            return $modules;
        });
    }
}
