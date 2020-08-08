<?php

namespace App\Providers;

use App\Settings\GeneralSettings;
use Hook;
use Illuminate\Support\ServiceProvider;

class SettingsPageProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Hook::addFilter( 'ns.settings', function( $class, $identifier ) {
            switch( $identifier ) {
                case 'ns.general': return new GeneralSettings; break;
            }
            return $class;
        }, 10, 2 );
    }
}
