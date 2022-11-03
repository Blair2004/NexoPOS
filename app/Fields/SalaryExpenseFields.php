<?php

namespace App\Fields;

use App\Classes\Hook;
use App\Models\AccountType;
use App\Models\Role;
use App\Services\FieldsService;
use App\Services\Helper;

class SalaryExpenseFields extends FieldsService
{
    protected $identifier = 'ns.salary-expenses';

    public function get()
    {
        $fields = Hook::filter( 'ns-direct-expenses-fields', [
            [
                'label' => __( 'Name' ),
                'description' => __( 'Describe the direct expense.' ),
                'validation' => 'required|min:5',
                'name' => 'name',
                'type' => 'text',
            ], [
                'label' =>  __( 'Category' ),
                'description'   =>  __( 'Assign the expense to a category.' ),
                'validation'    =>  'required',
                'name'      =>  'category_id',
                'options'   =>  Helper::toJsOptions( AccountType::get(), [ 'id', 'name' ]),
                'type'      =>  'select',
            ], [
                'label' =>  __( 'Value' ),
                'description'   =>  __( 'set the value of the expense.' ),
                'validation'    =>  'required',
                'name'      =>  'value',
                'type'      =>  'number',
            ], [
                'label' =>  __( 'User Group' ),
                'description'   =>  __( 'The expenses will be multipled by the number of user having that role.' ),
                'validation'    =>  'required',
                'name'      =>  'category_id',
                'options'   =>  Helper::toJsOptions( Role::get()->map( function( $role ) {
                    $role->name     .= ' (' . $role->users()->count() . ')';
                    return $role;
                }), [ 'id', 'name' ]),
                'type'      =>  'select',
            ], [
                'label' =>  __( 'Description' ),
                'description'   =>  __( 'Further details on the expense.' ),
                'validation'    =>  'required',
                'name'      =>  'description',
                'type'      =>  'textarea',
            ]
        ]);

        return $fields;
    }
}
