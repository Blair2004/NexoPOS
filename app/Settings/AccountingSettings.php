<?php

namespace App\Settings;

use App\Classes\SettingForm;
use App\Models\TransactionAccount;
use App\Services\Helper;
use App\Services\SettingsPage;

class AccountingSettings extends SettingsPage
{
    const IDENTIFIER = 'accounting';

    const AUTOLOAD = true;

    public function __construct()
    {
        $accounting = config( 'accounting' );
        $accounts = collect( $accounting[ 'accounts' ] )->mapWithKeys( function ( $account, $key ) {
            return [ $key => Helper::toJsOptions( TransactionAccount::where( 'category_identifier', $key )->where( 'sub_category_id', '!=', null )->get(), [ 'id', 'name' ] ) ];
        } );

        $this->form = [
            'title' => __( 'Accounting' ),
            'description' => __( 'Configure the accounting feature' ),
            'tabs' => SettingForm::tabs(
                SettingForm::tab(
                    identifier: 'general',
                    label: __( 'General' ),
                    fields: include ( dirname( __FILE__ ) . '/accounting/general.php' ),
                    footer: SettingForm::tabFooter(
                        extraComponents : [ 'nsDefaultAccounting' ] // components defined on "resources/ts/components"
                    )
                ),
            ),
        ];
    }
}
