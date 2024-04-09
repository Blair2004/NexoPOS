<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Crud\CustomerCrud;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Services\Helper;

return SettingForm::tab(
    identifier: 'general',
    label: __( 'General' ),
    fields: SettingForm::fields(
        FormInput::select(
            label: __( 'Enable Reward' ),
            name: 'ns_customers_rewards_enabled',
            value: ns()->option->get( 'ns_customers_rewards_enabled', 'no' ),
            description: __( 'Will activate the reward system for the customers.' ),
            options: Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
        ),
        FormInput::select(
            label: __( 'Require Valid Email' ),
            name: 'ns_customers_force_valid_email',
            value: ns()->option->get( 'ns_customers_force_valid_email', 'no' ),
            description: __( 'Will for valid unique email for every customer.' ),
            options: Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
        ),
        FormInput::select(
            label: __( 'Require Unique Phone' ),
            name: 'ns_customers_force_unique_phone',
            value: ns()->option->get( 'ns_customers_force_unique_phone', 'no' ),
            description: __( 'Every customer should have a unique phone number.' ),
            options: Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
        ),
        FormInput::searchSelect(
            label: __( 'Default Customer Account' ),
            name: 'ns_customers_default',
            component: 'nsCrudForm',
            props: CustomerCrud::getFormConfig(),
            value: ns()->option->get( 'ns_customers_default', 'no' ),
            description: __( 'You must create a customer to which each sales are attributed when the walking customer doesn\'t register.' ),
            options: Helper::toJsOptions( Customer::get(), [ 'id', [ 'first_name', 'last_name' ] ] ),
        ),
        FormInput::select(
            label: __( 'Default Customer Group' ),
            name: 'ns_customers_default_group',
            value: ns()->option->get( 'ns_customers_default_group', 'no' ),
            description: __( 'Select to which group each new created customers are assigned to.' ),
            options: Helper::toJsOptions( CustomerGroup::get(), [ 'id', 'name' ] ),
        ),
        FormInput::select(
            label: __( 'Enable Credit & Account' ),
            name: 'ns_customers_credit_enabled',
            value: ns()->option->get( 'ns_customers_credit_enabled', 'no' ),
            description: __( 'The customers will be able to make deposit or obtain credit.' ),
            options: Helper::kvToJsOptions( [
                'yes' => __( 'Yes' ),
                'no' => __( 'No' ),
            ] ),
        ),
    )
);
