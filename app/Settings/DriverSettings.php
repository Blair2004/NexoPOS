<?php

namespace App\Settings;

use App\Services\SettingsPage;
use App\Classes\SettingForm;
use App\Classes\FormInput;
use App\Services\Helper;

class DriverSettings extends SettingsPage
{
    /**
     * The form will be automatically loaded.
     * You might prevent this by setting "autoload" to false.
     */
    const AUTOLOAD  =   true;

    /**
     * A unique identifier provided to the form, 
     * that helps NexoPOS distinguish it among other forms.
     */
    const IDENTIFIER = 'drivers';

    public function __construct()
    {
        /**
         * Settings Form definition.
         */
        $this->form     =   SettingForm::form(
            title: __( 'Settings' ),
            description: __( 'Configure the driver feature.' ),
            tabs: SettingForm::tabs(
                SettingForm::tab(
                    label: __( 'General Settings' ),
                    identifier: 'general',
                    fields: SettingForm::fields(
                        FormInput::switch(
                            label: __( 'Enable Driver Feature' ),
                            name: 'ns_drivers_enabled',
                            value: ns()->option->get( 'ns_drivers_enabled', 'no' ),
                            description: __( 'Enable or disable the driver feature.' ),
                            options: Helper::kvToJsOptions( [ 'yes' => __( 'Yes' ), 'no' => __( 'No' ) ] )
                        ),
                        FormInput::switch(
                            label: __( 'Force Driver Selection' ),
                            name: 'ns_drivers_force_selection',
                            value: ns()->option->get( 'ns_drivers_force_selection' ),
                            description: __( 'Will force the user to select a driver for delivery order.' ),
                            show: ns()->option->get( 'ns_drivers_enabled', 'no' ) === 'yes',
                            options: Helper::kvToJsOptions( [
                                'yes' => __( 'Yes' ),
                                'no' => __( 'No' ),
                            ] ),
                        ),
                        FormInput::select(
                            label: __( 'Driver Payment Type' ),
                            name: 'ns_drivers_payment_type',
                            value: ns()->option->get( 'ns_drivers_payment_type', 'fixed' ),
                            description: __( 'Choose how drivers are paid for deliveries.' ),
                            show: ns()->option->get( 'ns_drivers_enabled', 'no' ) === 'yes',
                            options: Helper::kvToJsOptions( [
                                'fixed' => __( 'Fixed Rate' ),
                                'percentage' => __( 'Percentage of Delivery Fee' ),
                            ] ),
                        ),
                        FormInput::number(
                            label: __( 'Fixed Payment Rate' ),
                            name: 'ns_drivers_fixed_rate',
                            value: ns()->option->get( 'ns_drivers_fixed_rate', '0' ),
                            description: __( 'Fixed amount paid to drivers per delivery.' ),
                            show: ns()->option->get( 'ns_drivers_enabled', 'no' ) === 'yes' && ns()->option->get( 'ns_drivers_payment_type', 'fixed' ) === 'fixed',
                        ),
                        FormInput::number(
                            label: __( 'Percentage Rate' ),
                            name: 'ns_drivers_percentage_rate',
                            value: ns()->option->get( 'ns_drivers_percentage_rate', '10' ),
                            description: __( 'Percentage of delivery fee paid to drivers (0-100).' ),
                            show: ns()->option->get( 'ns_drivers_enabled', 'no' ) === 'yes' && ns()->option->get( 'ns_drivers_payment_type', 'fixed' ) === 'percentage',
                        ),
                    )
                )
            )
        );
    }
}
