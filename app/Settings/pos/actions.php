<?php

use App\Classes\FormInput;
use App\Classes\SettingForm;
use App\Services\Helper;

return SettingForm::tab(
    identifier: 'pos_actions',
    label: __( 'Action Permissions' ),
    fields: SettingForm::fields(
        FormInput::select(
            label: __( 'Permission Duration' ),
            name: 'ns_pos_action_permission_duration',
            options: Helper::kvToJsOptions( [
                '1' => __( '1 Minute' ),
                '5' => __( '5 Minutes' ),
                '10' => __( '10 Minutes' ),
            ] ),
            description: __( 'Define the duration of the action permission.' ),
            value: ns()->option->get( 'ns_pos_action_permission_duration', '5' )
        ),
        FormInput::multiselect(
            label: __( 'Restricted Features' ),
            name: 'ns_pos_action_permission_restricted_features',
            options: Helper::kvToJsOptions( [
                'nexopos.cart.product-discount' => __( 'Cart: Change Product Discount' ),
                'nexopos.cart.product-price' => __( 'Cart: Edit Product Price' ),
                'nexopos.cart.product-wholesale-price' => __( 'Cart: Use Wholesale Price' ),
                'nexopos.cart.product-delete' => __( 'Cart: Product Delete' ),
                'nexopos.cart.settings' => __( 'Cart: Change Settings' ),
                'nexopos.cart.taxes' => __( 'Cart: Set Taxes' ),
                'nexopos.cart.comments' => __( 'Cart: Add Comments' ),
                'nexopos.cart.order-type' => __( 'Cart: Change Order Type' ),
                'nexopos.cart.coupons' => __( 'Cart: Apply Coupons' ),
                'nexopos.cart.products' => __( 'Cart: Create Quick Product' ),
                'nexopos.cart.void' => __( 'Cart: Void Order' ),
                'nexopos.cart.discount' => __( 'Cart: Apply Discount' ),
                'nexopos.cart.hold' => __( 'Cart: Hold Order' ),
            ] ),
            value: ns()->option->get( 'ns_pos_action_permission_restricted_features', [] ),
            description: __( 'Select the features that will be restricted for the user.' ),
        ),
        FormInput::select(
            label: __( 'Cooldown Before New Request' ),
            name: 'ns_pos_action_permission_cooldown_features',
            options: Helper::kvToJsOptions( [
                '0' => __( 'No Cooldown' ),
                '5' => __( '5 Minutes' ),
                '10' => __( '10 Minutes' ),
                '15' => __( '15 Minutes' ),
                '30' => __( '30 Minutes' ),
                '60' => __( '1 Hour' ),
            ] ),
            description: __( 'Define the cooldown period before a user can request a new permission.' ),
            value: ns()->option->get( 'ns_pos_action_permission_cooldown_features', '5' )
        )
    )
);
