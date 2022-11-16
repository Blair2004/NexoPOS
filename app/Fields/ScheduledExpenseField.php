<?php

namespace App\Fields;

use App\Classes\Hook;
use App\Models\AccountType;
use App\Models\Expense;
use App\Services\FieldsService;
use App\Services\Helper;

class ScheduledExpenseField extends FieldsService
{
    protected static $identifier = 'ns.scheduled-expenses';

    public function __construct( Expense $expense = null )
    {
        $this->fields = Hook::filter( 'ns-scheduled-expenses-fields', [
            [
                'label' => __( 'Name' ),
                'description' => __( 'Describe the direct expense.' ),
                'validation' => 'required|min:5',
                'name' => 'name',
                'type' => 'text',
            ], [
                'label' => __( 'Scheduled On' ),
                'description' => __( 'Set when the expense should be executed.' ),
                'validation' => 'required',
                'name' => 'scheduled_date',
                'type' => 'datetimepicker',
            ], [
                'label' => __( 'Activated' ),
                'validation' => 'required',
                'name' => 'active',
                'description' => __( 'If set to yes, the expense will be eligible for an execution.' ),
                'options' => Helper::kvToJsOptions([ false => __( 'No' ), true => __( 'Yes' )]),
                'type' => 'switch',
            ], [
                'label' => __( 'Category' ),
                'description' => __( 'Assign the expense to a category.' ),
                'validation' => 'required',
                'name' => 'category_id',
                'options' => Helper::toJsOptions( AccountType::get(), [ 'id', 'name' ]),
                'type' => 'select',
            ], [
                'label' => __( 'Value' ),
                'description' => __( 'set the value of the expense.' ),
                'validation' => 'required',
                'name' => 'value',
                'type' => 'number',
            ], [
                'label' => __( 'Description' ),
                'description' => __( 'Further details on the expense.' ),
                'name' => 'description',
                'type' => 'textarea',
            ], [
                'label' => __( 'Recurring' ),
                'validation' => 'required',
                'name' => 'recurring',
                'type' => 'hidden',
            ], [
                'label' => __( 'type' ),
                'validation' => 'required',
                'name' => 'type',
                'type' => 'hidden',
            ],
        ]);

        if ( $expense instanceof Expense ) {
            foreach ( $this->fields as $key => $field ) {
                if ( isset( $expense->{$field[ 'name' ]} ) ) {
                    if ( is_bool( $expense->{$field[ 'name' ]} ) ) {
                        $this->fields[$key][ 'value' ] = (int) $expense->{$field[ 'name' ]};
                    } else {
                        $this->fields[$key][ 'value' ] = $expense->{$field[ 'name' ]};
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
