<?php

namespace App\Settings;

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Crud\TransactionAccountCrud;
use App\Models\TransactionAccount;
use App\Services\Helper;
use App\Services\SettingsPage;

class AccountingSettings extends SettingsPage
{
    const IDENTIFIER = 'accounting';

    const AUTOLOAD = true;

    public function __construct()
    {
        $accounts = TransactionAccount::get()->map( function ( $account ) {
            return [
                'label' => $account->name,
                'value' => $account->id,
            ];
        } );

        $accounting     =   config( 'accounting' );
        $accounts   =   collect( $accounting[ 'accounts' ] )->mapWithKeys( function( $account, $key ) {
            return [ $key => Helper::toJsOptions( TransactionAccount::where( 'category_identifier', $key )->get(), [ 'id', 'name' ] ) ];
        });

        $this->form = [
            'title' => __( 'Accounting' ),
            'description' => __( 'Configure the accounting feature' ),
            'tabs' => SettingForm::tabs(
                SettingForm::tab(
                    identifier: 'general',
                    label: __( 'General' ),
                    fields: include ( dirname( __FILE__ ) . '/accounting/general.php' ),
                ),
                SettingForm::tab(
                    identifier: 'orders',
                    label: __( 'Orders' ),
                    fields: include ( dirname( __FILE__ ) . '/accounting/orders.php' ),
                ),
                SettingForm::tab(
                    identifier: 'procurements',
                    label: __( 'Procurements' ),
                    fields: include ( dirname( __FILE__ ) . '/accounting/procurements.php' ),
                ),
                SettingForm::tab(
                    identifier: 'cash-registers',
                    label: __( 'Cash Register' ),
                    fields: include ( dirname( __FILE__ ) . '/accounting/cash-registers.php' ),
                )
            ),
        ];
    }
}
