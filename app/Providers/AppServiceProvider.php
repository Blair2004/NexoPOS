<?php

namespace App\Providers;

use App\Services\CoreService;
use App\Services\CrudService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        include_once( base_path() . '/app/Services/HelperFunctions.php' );
        
        $this->app->singleton( CrudService::class, function() {
            return new CrudService;
        });

        $this->app->singleton( CoreService::class, function() {
            return new CoreService;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
