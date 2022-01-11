<?php

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Services\Helper;

return [
    'label'     =>  __( 'General' ),
    'fields'    =>  [
        [
            'type'  =>  'select',
            'label' =>  __( 'Enable Reward' ),
            'description'   =>  __( 'Will activate the reward system for the customers.' ),
            'name'          =>  'ns_customers_rewards_enabled',
            'value'         =>  $options->get( 'ns_customers_rewards_enabled', 'no' ),
            'options'       =>  Helper::kvToJsOptions([
                'yes'       =>  __( 'Yes' ),
                'no'        =>  __( 'No' )
            ])
        ], [
            'type'  =>  'select',
            'label' =>  __( 'Require Valid Email' ),
            'description'   =>  __( 'Will for valid unique email for every customer.' ),
            'name'          =>  'ns_customers_force_valid_email',
            'value'         =>  $options->get( 'ns_customers_force_valid_email', 'no' ),
            'options'       =>  Helper::kvToJsOptions([
                'yes'       =>  __( 'Yes' ),
                'no'        =>  __( 'No' )
            ])
        ], [
            'type'  =>  'select',
            'label' =>  __( 'Default Customer Account' ),
            'description'   =>  __( 'You must create a customer to which each sales are attributed when the walking customer doesn\'t register.' ),
            'name'          =>  'ns_customers_default',
            'value'         =>  $options->get( 'ns_customers_default', 'no' ),
            'options'       =>  Helper::toJsOptions( Customer::get(), [ 'id', 'name' ])
        ], [
            'type'  =>  'select',
            'label' =>  __( 'Default Customer Group' ),
            'description'   =>  __( 'Select to which group each new created customers are assigned to.' ),
            'name'          =>  'ns_customers_default_group',
            'value'         =>  $options->get( 'ns_customers_default_group', 'no' ),
            'options'       =>  Helper::toJsOptions( CustomerGroup::get(), [ 'id', 'name' ])
        ], [
            'type'  =>  'select',
            'label' =>  __( 'Enable Credit & Account' ),
            'description'   =>  __( 'The customers will be able to make deposit or obtain credit.' ),
            'name'          =>  'ns_customers_credit_enabled',
            'value'         =>  $options->get( 'ns_customers_credit_enabled', 'no' ),
            'options'       =>  Helper::kvToJsOptions([
                'yes'       =>  __( 'Yes' ),
                'no'        =>  __( 'No' )
            ])
        ], 
    ]
];