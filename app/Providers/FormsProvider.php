<?php

namespace App\Providers;

use App\Fields\AuthLoginFields;
use App\Fields\AuthRegisterFields;
use App\Fields\CashRegisterCashingFields;
use App\Fields\CashRegisterCashoutFields;
use App\Fields\CashRegisterClosingFields;
use App\Fields\CashRegisterOpeningFields;
use App\Fields\CustomersAccountFields;
use App\Fields\DirectTransactionFields;
use App\Fields\EntityTransactionFields;
use App\Fields\LayawayFields;
use App\Fields\NewPasswordFields;
use App\Fields\OrderPaymentFields;
use App\Fields\PasswordLostFields;
use App\Fields\PosOrderSettingsFields;
use App\Fields\ProcurementFields;
use App\Fields\ReccurringTransactionFields;
use App\Fields\RefundProductFields;
use App\Fields\ResetFields;
use App\Fields\ScheduledTransactionFields;
use App\Fields\UnitsFields;
use App\Fields\UnitsGroupsFields;
use App\Forms\POSAddressesForm;
use App\Forms\ProcurementForm;
use App\Forms\UserProfileForm;
use Illuminate\Support\ServiceProvider;
use TorMorten\Eventy\Facades\Events as Hook;

class FormsProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        // ...
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Hook::addFilter( 'ns.forms', function ( $class, $identifier ) {
            switch ( $identifier ) {
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

        Hook::addFilter( 'ns.fields', function ( $class, $identifier ) {
            switch ( $class ) {
                case AuthLoginFields::getIdentifier():
                    return new AuthLoginFields;
                    break;
                case PasswordLostFields::getIdentifier():
                    return new PasswordLostFields;
                    break;
                case NewPasswordFields::getIdentifier():
                    return new NewPasswordFields;
                    break;
                case AuthRegisterFields::getIdentifier():
                    return new AuthRegisterFields;
                    break;
                case CustomersAccountFields::getIdentifier():
                    return new CustomersAccountFields;
                    break;
                case LayawayFields::getIdentifier():
                    return new LayawayFields;
                    break;
                case RefundProductFields::getIdentifier():
                    return new RefundProductFields;
                    break;
                case CashRegisterOpeningFields::getIdentifier():
                    return new CashRegisterOpeningFields;
                    break;
                case CashRegisterClosingFields::getIdentifier():
                    return new CashRegisterClosingFields;
                    break;
                case CashRegisterCashingFields::getIdentifier():
                    return new CashRegisterCashingFields;
                    break;
                case CashRegisterCashoutFields::getIdentifier():
                    return new CashRegisterCashoutFields;
                    break;
                case PosOrderSettingsFields::getIdentifier():
                    return new PosOrderSettingsFields;
                    break;
                case OrderPaymentFields::getIdentifier():
                    return new OrderPaymentFields;
                    break;
                case ProcurementFields::getIdentifier():
                    return new ProcurementFields;
                    break;
                case UnitsFields::getIdentifier():
                    return new UnitsFields;
                    break;
                case DirectTransactionFields::getIdentifier():
                    return new DirectTransactionFields;
                    break;
                case ReccurringTransactionFields::getIdentifier():
                    return new ReccurringTransactionFields;
                    break;
                case EntityTransactionFields::getIdentifier():
                    return new EntityTransactionFields;
                    break;
                case ScheduledTransactionFields::getIdentifier():
                    return new ScheduledTransactionFields;
                    break;
                case UnitsGroupsFields::getIdentifier():
                    return new UnitsGroupsFields;
                    break;
                case ResetFields::getIdentifier():
                    return new ResetFields;
                    break;
                default:
                    return $class;
                    break;
            }
        }, 10, 2 );
    }
}
