<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CrudService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton( CrudService::class, function() {
            return new CrudService;
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
