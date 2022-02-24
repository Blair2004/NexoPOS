<?php

namespace App\Providers;

use App\Fields\AuthLoginFields;
use App\Fields\AuthRegisterFields;
use App\Fields\CashRegisterCashingFields;
use App\Fields\CashRegisterCashoutFields;
use App\Fields\CashRegisterOpeningFields;
use App\Fields\CashRegisterClosingFields;
use App\Fields\PasswordLostFields;
use App\Fields\NewPasswordFields;
use App\Fields\CustomersAccountFields;
use App\Fields\LayawayFields;
use App\Fields\PosOrderSettingsFields;
use App\Fields\RefundProductFields;
use App\Fields\ResetFields;
use App\Forms\POSAddressesForm;
use App\Forms\ProcurementForm;
use App\Forms\UserProfileForm;
use TorMorten\Eventy\Facades\Events as Hook;
use Illuminate\Support\ServiceProvider;

class FormsProvider extends ServiceProvider
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
        Hook::addFilter( 'ns.forms', function( $class, $identifier ) {
            switch( $identifier ) {
                case 'ns.user-profile': 
                    return new UserProfileForm; 
                break;
                case 'ns.procurement':
                    return new ProcurementForm;
                break;
                case 'ns.pos-addresses':
                    return new POSAddressesForm;
                break;
            }
            return $class;
        }, 10, 2 );

        Hook::addFilter( 'ns.fields', function( $class, $identifier ) {
            switch( $class ) {
                case 'ns.login' :
                    return new AuthLoginFields;
                break;
                case 'ns.password-lost' :
                    return new PasswordLostFields;
                break;
                case 'ns.new-password' :
                    return new NewPasswordFields;
                break;
                case 'ns.register' :
                    return new AuthRegisterFields;
                break;
                case 'ns.customers-account' :
                    return new CustomersAccountFields;
                break;
                case 'ns.layaway' :
                    return new LayawayFields;
                break;
                case 'ns.refund-product':
                    return new RefundProductFields;
                break;    
                case 'ns.cash-registers-opening':
                    return new CashRegisterOpeningFields;
                break;            
                case 'ns.cash-registers-closing':
                    return new CashRegisterClosingFields;
                break;            
                case 'ns.cash-registers-cashing':
                    return new CashRegisterCashingFields;
                break;            
                case 'ns.cash-registers-cashout':
                    return new CashRegisterCashoutFields;
                break;     
                case 'ns.pos-order-settings':
                    return new PosOrderSettingsFields;
                break;     
                case 'ns.reset':
                    return new ResetFields;
                break;
                default:
                    return $class;
                break;       
            }
        }, 10, 2 );
    }
}
