<?php

namespace App\Providers;

use App\Settings\AccountingSettings;
use App\Settings\CustomersSettings;
use App\Settings\GeneralSettings;
use App\Settings\InvoiceSettings;
use App\Settings\NotificationsSettings;
use App\Settings\OrdersSettings;
use App\Settings\PosSettings;
use App\Settings\ReportsSettings;
use App\Settings\StoresSettings;
use App\Settings\SuppliesDeliveriesSettings;
use App\Settings\WorkersSettings;
use App\Settings\ServiceProvidersSettings;
use TorMorten\Eventy\Facades\Events as Hook;
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
                case 'ns.service-providers': return new ServiceProvidersSettings; break;
                case 'ns.invoice-settings': return new InvoiceSettings; break;
                case 'ns.notifications': return new NotificationsSettings; break;
                case 'ns.workers': return new WorkersSettings; break;
                case 'ns.reports': return new ReportsSettings; break;
                case 'ns.accounting': return new AccountingSettings; break;
            }
            return $class;
        }, 10, 2 );
    }
}
