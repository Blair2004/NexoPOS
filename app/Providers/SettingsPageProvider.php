<?php

namespace App\Providers;

use App\Forms\ResetForm;
use App\Settings\AccountingSettings;
use App\Settings\CustomersSettings;
use App\Settings\GeneralSettings;
use App\Settings\InvoiceSettings;
use App\Settings\OrdersSettings;
use App\Settings\PosSettings;
use App\Settings\ReportsSettings;
use App\Settings\SuppliesDeliveriesSettings;
use App\Settings\WorkersSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use TorMorten\Eventy\Facades\Events as Hook;

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
            $classes = Cache::get( 'crud-classes', function() use ( $identifier ) {
                $files = collect( Storage::disk( 'ns' )->files( 'app/Settings' ) );

                dump( $files );

                return $files->map( fn( $file ) => 'App\Settings\\' . pathinfo( $file )[ 'filename' ] )
                    ->filter( fn( $class ) => ( defined( $class . '::AUTOLOAD' ) && defined( $class . '::IDENTIFIER' ) && $class::IDENTIFIER === $identifier && $class::AUTOLOAD === true ) );
            });

            dd( $identifier );

            /**
             * If there is a match, we'll return
             * the first class that matches.
             */
            if ( $classes->isNotEmpty() ) {
                return $classes->first();
            }
            
            switch ( $identifier ) {
                // case 'ns.general': return new GeneralSettings;
                //     break;
                case 'ns.pos': return new PosSettings;
                    break;
                case 'ns.customers': return new CustomersSettings;
                    break;
                case 'ns.supplies-deliveries': return new SuppliesDeliveriesSettings;
                    break;
                case 'ns.orders': return new OrdersSettings;
                    break;
                case 'ns.invoice-settings': return new InvoiceSettings;
                    break;
                case 'ns.workers': return new WorkersSettings;
                    break;
                case 'ns.reports': return new ReportsSettings;
                    break;
                case 'ns.accounting': return new AccountingSettings;
                    break;
                case 'ns.reset':
                    return new ResetForm;
                    break;
            }

            return $class;
        }, 10, 2 );
    }
}
