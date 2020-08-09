<?php

namespace App\Providers;

use App\Settings\GeneralSettings;
use App\Settings\PosSettings;
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
                case 'ns.pos': return new PosSettings; break;
            }
            return $class;
        }, 10, 2 );
    }
}
