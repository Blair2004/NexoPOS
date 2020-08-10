<?php

namespace App\Providers;

use App\Settings\CustomersSettings;
use App\Settings\GeneralSettings;
use App\Settings\OrdersSettings;
use App\Settings\PosSettings;
use App\Settings\StoresSettings;
use App\Settings\SuppliesDeliveriesSettings;
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
                case 'ns.customers': return new CustomersSettings; break;
                case 'ns.supplies-deliveries': return new SuppliesDeliveriesSettings; break;
                case 'ns.orders': return new OrdersSettings; break;
                case 'ns.stores': return new StoresSettings; break;
            }
            return $class;
        }, 10, 2 );
    }
}
