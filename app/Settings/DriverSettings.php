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
            description: __( 'No description has been provided.' ),
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
                    )
                )
            )
        );
    }
}
