<?php

namespace App\Providers;

use App\Models\Driver;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        /**
         * This will ensure a correct route binding for the driver.
         * As we're using a scope that apply a jointure, we would like to avoid
         * an ambiguous "id" column error.
         */
        Route::bind( 'driver', function ( $value ) {
            return Driver::where( 'nexopos_users.id', $value )
                ->firstOrFail();
        } );
    }
}
