<?php

namespace App\Settings;

use App\Classes\FormInput;
use App\Classes\Hook;
use App\Classes\SettingForm;
use App\Models\Role;
use App\Services\Helper;
use App\Services\SettingsPage;

class GeneralSettings extends SettingsPage
{
    const IDENTIFIER = 'general';

    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = SettingForm::form(
            title: __( 'General Settings' ),
            description: __( 'Configure the general settings of the application.' ),
            tabs: SettingForm::tabs(
                $this->getIdentificationSettings(),
                $this->getCurrencySettings(),
                $this->getDateSettings(),
                $this->getRegistrationSettings(),
            )
        );
    }

    public function getIdentificationSettings()
    {
        return SettingForm::tab(
            label: __( 'Identification' ),
            identifier: 'identification',
            fields: SettingForm::fields(
                FormInput::text(
                    name: 'ns_store_name',
                    value: ns()->option->get( 'ns_store_name' ),
                    label: __( 'Store Name' ),
                    description: __( 'This is the store name.' ),
                    validation: 'required',
                ),
                FormInput::text(
                    name: 'ns_store_address',
                    value: ns()->option->get( 'ns_store_address' ),
                    label: __( 'Store Address' ),
                    description: __( 'The actual store address.' ),
                ),
                FormInput::text(
                    name: 'ns_store_city',
                    value: ns()->option->get( 'ns_store_city' ),
                    label: __( 'Store City' ),
                    description: __( 'The actual store city.' ),
                ),
                FormInput::text(
                    name: 'ns_store_phone',
                    value: ns()->option->get( 'ns_store_phone' ),
                    label: __( 'Store Phone' ),
                    description: __( 'The phone number to reach the store.' ),
                ),
                FormInput::text(
                    name: 'ns_store_email',
                    value: ns()->option->get( 'ns_store_email' ),
                    label: __( 'Store Email' ),
                    description: __( 'The actual store email. Might be used on invoice or for reports.' ),
                ),
                FormInput::text(
                    name: 'ns_store_pobox',
                    value: ns()->option->get( 'ns_store_pobox' ),
                    label: __( 'Store PO.Box' ),
                    description: __( 'The store mail box number.' ),
                ),
                FormInput::text(
                    name: 'ns_store_fax',
                    value: ns()->option->get( 'ns_store_fax' ),
                    label: __( 'Store Fax' ),
                    description: __( 'The store fax number.' ),
                ),
                FormInput::textarea(
                    name: 'ns_store_additional',
                    value: ns()->option->get( 'ns_store_additional' ),
                    label: __( 'Store Additional Information' ),
                    description: __( 'Store additional information.' ),
                ),
                FormInput::media(
                    name: 'ns_store_square_logo',
                    value: ns()->option->get( 'ns_store_square_logo' ),
                    label: __( 'Store Square Logo' ),
                    description: __( 'Choose what is the square logo of the store.' ),
                ),
                FormInput::media(
                    name: 'ns_store_rectangle_logo',
                    value: ns()->option->get( 'ns_store_rectangle_logo' ),
                    label: __( 'Store Rectangle Logo' ),
                    description: __( 'Choose what is the rectangle logo of the store.' ),
                ),
                FormInput::select(
                    name: 'ns_store_language',
                    value: ns()->option->get( 'ns_store_language' ),
                    options: Helper::kvToJsOptions( config( 'nexopos.languages' ) ),
                    label: __( 'Language' ),
                    description: __( 'Define the default fallback language.' ),
                ),
                FormInput::select(
                    name: 'ns_default_theme',
                    value: ns()->option->get( 'ns_default_theme' ),
                    options: Helper::kvToJsOptions( [
                        'dark' => __( 'Dark' ),
                        'light' => __( 'Light' ),
                    ] ),
                    label: __( 'Theme' ),
                    description: __( 'Define the default theme.' ),
                ),
            )
        );
    }

    public function getCurrencySettings()
    {
        return SettingForm::tab(
            label: __( 'Currency' ),
            identifier: 'currency',
            fields: SettingForm::fields(
                FormInput::text(
                    name: 'ns_currency_symbol',
                    value: ns()->option->get( 'ns_currency_symbol' ),
                    label: __( 'Currency Symbol' ),
                    description: __( 'This is the currency symbol.' ),
                    validation: 'required',
                ),
                FormInput::text(
                    name: 'ns_currency_iso',
                    value: ns()->option->get( 'ns_currency_iso' ),
                    label: __( 'Currency ISO' ),
                    description: __( 'The international currency ISO format.' ),
                    validation: 'required',
                ),
                FormInput::select(
                    name: 'ns_currency_position',
                    value: ns()->option->get( 'ns_currency_position' ),
                    options: [
                        [
                            'label' => __( 'Before the amount' ),
                            'value' => 'before',
                        ], [
                            'label' => __( 'After the amount' ),
                            'value' => 'after',
                        ],
                    ],
                    label: __( 'Currency Position' ),
                    description: __( 'Define where the currency should be located.' ),
                ),
                FormInput::select(
                    name: 'ns_currency_prefered',
                    value: ns()->option->get( 'ns_currency_prefered' ),
                    options: [
                        [
                            'label' => __( 'ISO Currency' ),
                            'value' => 'iso',
                        ], [
                            'label' => __( 'Symbol' ),
                            'value' => 'symbol',
                        ],
                    ],
                    label: __( 'Preferred Currency' ),
                    description: __( 'Determine what is the currency indicator that should be used.' ),
                ),
                FormInput::text(
                    name: 'ns_currency_thousand_separator',
                    value: ns()->option->get( 'ns_currency_thousand_separator' ),
                    label: __( 'Currency Thousand Separator' ),
                    description: __( 'Define the symbol that indicate thousand. By default "," is used.' ),
                ),
                FormInput::text(
                    name: 'ns_currency_decimal_separator',
                    value: ns()->option->get( 'ns_currency_decimal_separator' ),
                    label: __( 'Currency Decimal Separator' ),
                    description: __( 'Define the symbol that indicate decimal number. By default "." is used.' ),
                ),
                FormInput::select(
                    name: 'ns_currency_precision',
                    value: ns()->option->get( 'ns_currency_precision', '0' ),
                    options: collect( [0, 1, 2, 3, 4, 5] )->map( function ( $index ) {
                        return [
                            'label' => sprintf( __( '%s numbers after the decimal' ), $index ),
                            'value' => $index,
                        ];
                    } )->toArray(),
                    label: __( 'Currency Precision' ),
                    description: __( 'Define where the currency should be located.' ),
                ),
            )
        );
    }

    public function getDateSettings()
    {
        return SettingForm::tab(
            label: __( 'Date' ),
            identifier: 'date',
            fields: SettingForm::fields(
                FormInput::select(
                    label: __( 'Date Format' ),
                    name: 'ns_date_format',
                    value: ns()->option->get( 'ns_date_format' ),
                    options: Helper::kvToJsOptions( [
                        'Y-m-d' => ns()->date->format( 'Y-m-d' ),
                        'Y/m/d' => ns()->date->format( 'Y/m/d' ),
                        'd-m-y' => ns()->date->format( 'd-m-Y' ),
                        'd/m/y' => ns()->date->format( 'd/m/Y' ),
                        'M dS, Y' => ns()->date->format( 'M dS, Y' ),
                        'd M Y' => ns()->date->format( 'd M Y' ),
                        'd.m.Y' => ns()->date->format( 'd.m.Y' ),
                    ] ),
                    description: __( 'This define how the date should be defined. The default format is "Y-m-d".' ),
                ),
                FormInput::select(
                    label: __( 'Date Time Format' ),
                    name: 'ns_datetime_format',
                    value: ns()->option->get( 'ns_datetime_format' ),
                    options: Helper::kvToJsOptions( [
                        'Y-m-d H:i' => ns()->date->format( 'Y-m-d H:i' ),
                        'Y/m/d H:i' => ns()->date->format( 'Y/m/d H:i' ),
                        'd-m-y H:i' => ns()->date->format( 'd-m-Y H:i' ),
                        'd/m/y H:i' => ns()->date->format( 'd/m/Y H:i' ),
                        'M dS, Y H:i' => ns()->date->format( 'M dS, Y H:i' ),
                        'd M Y, H:i' => ns()->date->format( 'd M Y, H:i' ),
                        'd.m.Y, H:i' => ns()->date->format( 'd.m.Y, H:i' ),
                    ] ),
                    description: __( 'This define how the date and times hould be formated. The default format is "Y-m-d H:i".' ),
                ),
                FormInput::select(
                    label: sprintf( __( 'Date TimeZone' ) ),
                    name: 'ns_datetime_timezone',
                    value: ns()->option->get( 'ns_datetime_timezone' ),
                    type: 'search-select',
                    options: Helper::kvToJsOptions( config( 'nexopos.timezones' ) ),
                    description: sprintf( __( 'Determine the default timezone of the store. Current Time: %s' ), ns()->date->getNowFormatted() ),
                ),
            )
        );
    }

    public function getRegistrationSettings()
    {
        return SettingForm::tab(
            label: __( 'Registration' ),
            identifier: 'registration',
            fields: SettingForm::fields(
                FormInput::select(
                    name: 'ns_registration_enabled',
                    value: ns()->option->get( 'ns_registration_enabled' ),
                    options: Helper::kvToJsOptions( [
                        'yes' => __( 'Yes' ),
                        'no' => __( 'No' ),
                    ] ),
                    label: __( 'Registration Open' ),
                    description: __( 'Determine if everyone can register.' ),
                ),
                FormInput::select(
                    name: 'ns_registration_role',
                    value: ns()->option->get( 'ns_registration_role' ),
                    options: Helper::toJsOptions( Hook::filter( 'ns-registration-roles', Role::get() ), [ 'id', 'name' ] ),
                    label: __( 'Registration Role' ),
                    description: __( 'Select what is the registration role.' ),
                ),
                FormInput::select(
                    name: 'ns_registration_validated',
                    value: ns()->option->get( 'ns_registration_validated' ),
                    options: Helper::kvToJsOptions( [
                        'yes' => __( 'Yes' ),
                        'no' => __( 'No' ),
                    ] ),
                    label: __( 'Requires Validation' ),
                    description: __( 'Force account validation after the registration.' ),
                ),
                FormInput::switch(
                    name: 'ns_recovery_enabled',
                    value: ns()->option->get( 'ns_recovery_enabled' ),
                    options: Helper::kvToJsOptions( [
                        'yes' => __( 'Yes' ),
                        'no' => __( 'No' ),
                    ] ),
                    label: __( 'Allow Recovery' ),
                    description: __( 'Allow any user to recover his account.' ),
                ),
            )
        );
    }
}
