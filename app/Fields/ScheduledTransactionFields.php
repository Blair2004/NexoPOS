<?php

namespace App\Fields;

use App\Classes\Hook;
use App\Models\Transaction;
use App\Models\TransactionAccount;
use App\Services\FieldsService;
use App\Services\Helper;

class ScheduledTransactionFields extends FieldsService
{
    protected static $identifier = Transaction::TYPE_SCHEDULED;

    public function __construct(?Transaction $transaction = null)
    {
        $this->fields = Hook::filter('ns-scheduled-transactions-fields', [
            [
                'label' => __('Name'),
                'description' => __('Describe the direct transaction.'),
                'validation' => 'required|min:5',
                'name' => 'name',
                'type' => 'text',
            ], [
                'label' => __('Scheduled On'),
                'description' => __('Set when the transaction should be executed.'),
                'validation' => 'required',
                'name' => 'scheduled_date',
                'type' => 'datetimepicker',
            ], [
                'label' => __('Activated'),
                'validation' => 'required',
                'name' => 'active',
                'description' => __('If set to yes, the transaction will take effect immediately and be saved on the history.'),
                'options' => Helper::kvToJsOptions([ false => __('No'), true => __('Yes')]),
                'type' => 'switch',
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
                'validation' => 'required',
                'name' => 'recurring',
                'type' => 'hidden',
            ], [
                'label' => __('type'),
                'validation' => 'required',
                'name' => 'type',
                'type' => 'hidden',
            ],
        ]);

        if ($transaction instanceof Transaction) {
            foreach ($this->fields as $key => $field) {
                if (isset($transaction->{$field[ 'name' ]})) {
                    if (is_bool($transaction->{$field[ 'name' ]})) {
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
