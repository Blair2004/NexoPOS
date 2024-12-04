<?php

namespace App\Fields;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Classes\SettingForm;
use App\Crud\TransactionAccountCrud;
use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Services\FieldsService;
use App\Services\Helper;

class ScheduledTransactionFields extends FieldsService
{
    protected static $identifier = Transaction::TYPE_SCHEDULED;

    public function __construct( ?Transaction $transaction = null )
    {
        $allowedExpenseCategories = ns()->option->get( 'ns_accounting_expenses_accounts', [] );

        $accountOptions = TransactionAccount::categoryIdentifier( 'expenses' )->whereIn( 'id', $allowedExpenseCategories )->get();

        $this->fields = Hook::filter( 'ns-scheduled-transactions-fields', SettingForm::fields(
            FormInput::text(
                label: __( 'Name' ),
                description: __( 'Describe the direct transaction.' ),
                validation: 'required|min:5',
                name: 'name',
                value: $transaction ? $transaction->name : null
            ),
            FormInput::datetime(
                label: __( 'Scheduled On' ),
                description: __( 'Set when the transaction should be executed. This is only date and hour specific, minutes are ignored.' ),
                validation: 'required',
                name: 'scheduled_date',
                value: $transaction ? $transaction->scheduled_date : null
            ),
            FormInput::switch(
                label: __( 'Activated' ),
                validation: 'required|min:5',
                name: 'active',
                description: __( 'If set to yes, the transaction will take effect immediately and be saved on the history.' ),
                options: Helper::kvToJsOptions( [ '0' => __( 'No' ), '1' => __( 'Yes' )] ),
                value: $transaction ? $transaction->getOriginal( 'active' ) : '1'
            ),
            FormInput::searchSelect(
                label: __( 'Account' ),
                description: __( 'Assign the transaction to an account.' ),
                validation: 'required',
                name: 'account_id',
                props: TransactionAccountCrud::getFormConfig(),
                component: 'nsCrudForm',
                options: Helper::toJsOptions( $accountOptions, [ 'id', 'name' ] ),
                value: $transaction ? $transaction->account_id : null
            ),
            FormInput::number(
                label: __( 'Value' ),
                description: __( 'set the value of the transaction.' ),
                validation: 'required',
                name: 'value',
                value: $transaction ? $transaction->value : null
            ),
            FormInput::textarea(
                label: __( 'Description' ),
                description: __( 'Further details on the transaction.' ),
                name: 'description',
                value: $transaction ? $transaction->description : null
            ),
            FormInput::hidden(
                label: __( 'Recurring' ),
                validation: 'required',
                name: 'recurring',
                value: 0
            ),
            FormInput::hidden(
                label: __( 'type' ),
                validation: 'required',
                name: 'type',
                value: Transaction::TYPE_SCHEDULED
            )
        ) );

        if ( $transaction instanceof Transaction ) {
            foreach ( $this->fields as $key => $field ) {
                if ( isset( $transaction->{$field[ 'name' ]} ) ) {
                    if ( is_bool( $transaction->{$field[ 'name' ]} ) ) {
                        $this->fields[$key][ 'value' ] = (int) $transaction->{$field[ 'name' ]};
                    } else {
                        $this->fields[$key][ 'value' ] = $transaction->{$field[ 'name' ]};
                    }
                }
            }
        }
    }

    public function get()
    {
        return $this->fields;
    }
}
