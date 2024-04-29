<?php

namespace App\Settings;

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Models\TransactionAccount;
use App\Services\SettingsPage;

class AccountingSettings extends SettingsPage
{
    const IDENTIFIER = 'accounting';

    const AUTOLOAD = true;

    public function __construct()
    {
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
                    identifier: 'cash-registers',
                    label: __( 'Cash Register' ),
                    fields: SettingForm::fields(
                        FormInput::multiselect( 
                            label: __( 'Allowed Cash In Account' ),
                            name: 'ns_accounting_cashin_accounts',
                            description: __( 'Define on which accounts cashin transactions are allowed' ),
                            options: TransactionAccount::credit()->get()->map( function ( $account ) {
                                return [
                                    'label' => $account->name,
                                    'value' => $account->id,
                                ];
                            } ),
                            value: ns()->option->get( 'ns_accounting_cashin_accounts' ),
                        ),
                        FormInput::multiselect( 
                            label: __( 'Allowed Cash Out Account' ),
                            name: 'ns_accounting_cashout_accounts',
                            description: __( 'Define on which accounts cashout transactions are allowed' ),
                            options: TransactionAccount::debit()->get()->map( function ( $account ) {
                                return [
                                    'label' => $account->name,
                                    'value' => $account->id,
                                ];
                            } ),
                            value: ns()->option->get( 'ns_accounting_cashout_accounts' ),
                        ),
                        FormInput::select(
                            label: __( 'Opening Float Source' ),
                            name: 'ns_accounting_opening_float_source',
                            description: __( 'Select the account from which the opening float will be taken' ),
                            options: TransactionAccount::credit()->get()->map( function ( $account ) {
                                return [
                                    'label' => $account->name,
                                    'value' => $account->id,
                                ];
                            } ),
                        )
                    ),
                )
            ),
        ];
    }
}
