<?php

namespace App\Settings;

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Crud\TransactionAccountCrud;
use App\Models\TransactionAccount;
use App\Services\SettingsPage;

class AccountingSettings extends SettingsPage
{
    const IDENTIFIER = 'accounting';

    const AUTOLOAD = true;

    public function __construct()
    {
        $debitAccounts = TransactionAccount::debit()->get()->map( function ( $account ) {
            return [
                'label' => $account->name,
                'value' => $account->id,
            ];
        } );

        $creditAccount = TransactionAccount::credit()->get()->map( function ( $account ) {
            return [
                'label' => $account->name,
                'value' => $account->id,
            ];
        } );

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
                            name: 'ns_accounting_cashing_accounts',
                            description: __( 'Define on which accounts cashing transactions are allowed' ),
                            options: $debitAccounts,
                            value: ns()->option->get( 'ns_accounting_cashing_accounts' ),
                        ),
                        FormInput::searchSelect(
                            label: __( 'Default Cash In Account' ),
                            name: 'ns_accounting_default_cashing_account',
                            description: __( 'Select the account where cashing transactions will be posted' ),
                            options: $debitAccounts,
                            component: 'nsCrudForm',
                            props: TransactionAccountCrud::getFormConfig(),
                            value: ns()->option->get( 'ns_accounting_default_cashing_account' ),
                        ),
                        FormInput::multiselect(
                            label: __( 'Allowed Cash Out Account' ),
                            name: 'ns_accounting_cashout_accounts',
                            description: __( 'Define on which accounts cashout transactions are allowed' ),
                            options: $creditAccount,
                            value: ns()->option->get( 'ns_accounting_cashout_accounts' ),
                        ),
                        FormInput::searchSelect(
                            label: __( 'Default Cash Out Account' ),
                            name: 'ns_accounting_default_cashout_account',
                            description: __( 'Select the account where cash out transactions will be posted' ),
                            options: $creditAccount,
                            component: 'nsCrudForm',
                            props: TransactionAccountCrud::getFormConfig(),
                            value: ns()->option->get( 'ns_accounting_default_cashout_account' ),
                        ),
                        FormInput::searchSelect(
                            label: __( 'Opening Float Account' ),
                            name: 'ns_accounting_opening_float_account',
                            description: __( 'Select the account from which the opening float will be taken' ),
                            options: $debitAccounts,
                            component: 'nsCrudForm',
                            props: TransactionAccountCrud::getFormConfig(),
                            value: ns()->option->get( 'ns_accounting_opening_float_account' ),
                        ),
                        FormInput::searchSelect(
                            label: __( 'Closing Float Account' ),
                            name: 'ns_accounting_closing_float_account',
                            description: __( 'Select the account from which the closing float will be taken' ),
                            options: $creditAccount,
                            component: 'nsCrudForm',
                            props: TransactionAccountCrud::getFormConfig(),
                            value: ns()->option->get( 'ns_accounting_closing_float_account' ),
                        )
                    ),
                )
            ),
        ];
    }
}
