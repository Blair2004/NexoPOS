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

class DirectTransactionFields extends FieldsService
{
    protected static $identifier = Transaction::TYPE_DIRECT;

    public function __construct( ?Transaction $transaction = null )
    {
        $this->fields = Hook::filter( 'ns-direct-transactions-fields', SettingForm::fields(
            FormInput::text(
                label: __( 'Name' ),
                description: __( 'Describe the direct transaction.' ),
                validation: 'required|min:5',
                name: 'name',
                value: $transaction ? $transaction->name : null
            ),
            FormInput::switch(
                label: __( 'Activated' ),
                validation: 'required|min:5',
                name: 'active',
                description: __( 'If set to yes, the transaction will take effect immediately and be saved on the history.' ),
                options: Helper::kvToJsOptions( [ false => __( 'No' ), true => __( 'Yes' )] ),
                value: $transaction ? $transaction->active : true
            ),
            FormInput::searchSelect(
                label: __( 'Account' ),
                description: __( 'Assign the transaction to an account.' ),
                validation: 'required',
                name: 'account_id',
                props: TransactionAccountCrud::getFormConfig(),
                component: 'nsCrudForm',
                options: Helper::toJsOptions( TransactionAccount::get(), [ 'id', 'name' ] ),
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
                validation: 'required|min:5',
                name: 'recurring',
                value: $transaction ? $transaction->recurring : null
            ),
            FormInput::hidden(
                label: __( 'type' ),
                validation: 'required|min:5',
                name: 'type',
                value: $transaction ? $transaction->type : null
            ),
        ) );

        if ( $transaction instanceof Transaction ) {
            foreach ( $this->fields as $key => $field ) {
                if ( isset( $transaction->{$field[ 'name' ]} ) ) {
                    $this->fields[$key][ 'value' ] = $transaction->{$field[ 'name' ]};
                }
            }
        }
    }

    public function get()
    {
        return $this->fields;
    }
}
