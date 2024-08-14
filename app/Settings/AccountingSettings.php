<?php

namespace App\Settings;

use App\Classes\FormInput;
use App\Classes\Notice;
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
        $accounting     =   config( 'accounting' );
        $accounts   =   collect( $accounting[ 'accounts' ] )->mapWithKeys( function( $account, $key ) {
            return [ $key => Helper::toJsOptions( TransactionAccount::where( 'category_identifier', $key )->where( 'sub_category_id', '!=', null )->get(), [ 'id', 'name' ] ) ];
        });

        $this->form = [
            'title' => __( 'Accounting' ),
            'description' => __( 'Configure the accounting feature' ),
            'tabs' => SettingForm::tabs(
                SettingForm::tab(
                    identifier: 'orders',
                    label: __( 'Orders' ),
                    notices: [
                        Notice::info(
                            title: __( 'Double Bookkepping Entry' ),
                            description: __( 'For sales, you should have assigned unique accounts that has as counter account (or offset account) a Revenue account. The COGS Account has as counter account the inventory account.' ),
                        ),
                    ],
                    fields: include ( dirname( __FILE__ ) . '/accounting/orders.php' ),
                    footer: SettingForm::tabFooter(
                        extraComponents : [ 'nsDefaultAccounting' ] // components defined on "resources/ts/components"
                    )
                ),
                SettingForm::tab(
                    identifier: 'procurements',
                    label: __( 'Procurements' ),
                    notices: [
                        Notice::info(
                            title: __( 'Double Bookkepping Entry' ),
                            description: __( 'Each selected account must have a counter account for double bookkeepping to work. The counter account for each should be the Inventory Account.' ),
                        ),
                    ],
                    fields: include ( dirname( __FILE__ ) . '/accounting/procurements.php' ),
                ),
            ),
        ];
    }
}
