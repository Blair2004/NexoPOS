<?php

namespace App\Fields;

use App\Classes\Hook;
use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Services\FieldsService;
use App\Services\Helper;

class DirectTransactionFields extends FieldsService
{
    protected static $identifier = Transaction::TYPE_DIRECT;

    public function __construct(?Transaction $transaction = null)
    {
        $this->fields = Hook::filter('ns-direct-transactions-fields', [
            [
                'label' => __('Name'),
                'description' => __('Describe the direct transaction.'),
                'validation' => 'required|min:5',
                'name' => 'name',
                'type' => 'text',
            ], [
                'label' => __('Activated'),
                'validation' => 'required|min:5',
                'name' => 'active',
                'description' => __('If set to yes, the transaction will take effect immediately and be saved on the history.'),
                'options' => Helper::kvToJsOptions([ false => __('No'), true => __('Yes')]),
                'type' => 'switch',
                'value' => (int) true,
            ], [
                'label' => __('Account'),
                'description' => __('Assign the transaction to an account.'),
                'validation' => 'required',
                'name' => 'account_id',
                'options' => Helper::toJsOptions(TransactionAccount::get(), [ 'id', 'name' ]),
                'type' => 'select',
            ], [
                'label' => __('Value'),
                'description' => __('set the value of the transaction.'),
                'validation' => 'required',
                'name' => 'value',
                'type' => 'number',
            ], [
                'label' => __('Description'),
                'description' => __('Further details on the transaction.'),
                'name' => 'description',
                'type' => 'textarea',
            ], [
                'label' => __('Recurring'),
                'validation' => 'required|min:5',
                'name' => 'recurring',
                'type' => 'hidden',
            ], [
                'label' => __('type'),
                'validation' => 'required|min:5',
                'name' => 'type',
                'type' => 'hidden',
            ],
        ]);

        if ($transaction instanceof Transaction) {
            foreach ($this->fields as $key => $field) {
                if (isset($transaction->{$field[ 'name' ]})) {
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
