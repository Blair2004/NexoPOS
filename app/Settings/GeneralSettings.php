<?php

namespace App\Settings;

use App\Classes\Hook;
use App\Models\Role;
use App\Services\Helper;
use App\Services\SettingsPage;

class GeneralSettings extends SettingsPage
{
    const IDENTIFIER = 'general';

    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = [
            'title' => __( 'General Settings' ),
            'description' => __( 'Configure the general settings of the application.' ),
            'tabs' => [
                'identification' => [
                    'label' => __( 'Identification' ),
                    'fields' => [
                        [
                            'name' => 'ns_store_name',
                            'value' => ns()->option->get( 'ns_store_name' ),
                            'label' => __( 'Store Name' ),
                            'type' => 'text',
                            'description' => __( 'This is the store name.' ),
                            'validation' => 'required',
                        ], [
                            'name' => 'ns_store_address',
                            'value' => ns()->option->get( 'ns_store_address' ),
                            'label' => __( 'Store Address' ),
                            'type' => 'text',
                            'description' => __( 'The actual store address.' ),
                        ], [
                            'name' => 'ns_store_city',
                            'value' => ns()->option->get( 'ns_store_city' ),
                            'label' => __( 'Store City' ),
                            'type' => 'text',
                            'description' => __( 'The actual store city.' ),
                        ], [
                            'name' => 'ns_store_phone',
                            'value' => ns()->option->get( 'ns_store_phone' ),
                            'label' => __( 'Store Phone' ),
                            'type' => 'text',
                            'description' => __( 'The phone number to reach the store.' ),
                        ], [
                            'name' => 'ns_store_email',
                            'value' => ns()->option->get( 'ns_store_email' ),
                            'label' => __( 'Store Email' ),
                            'type' => 'text',
                            'description' => __( 'The actual store email. Might be used on invoice or for reports.' ),
                        ], [
                            'name' => 'ns_store_pobox',
                            'value' => ns()->option->get( 'ns_store_pobox' ),
                            'label' => __( 'Store PO.Box' ),
                            'type' => 'text',
                            'description' => __( 'The store mail box number.' ),
                        ], [
                            'name' => 'ns_store_fax',
                            'value' => ns()->option->get( 'ns_store_fax' ),
                            'label' => __( 'Store Fax' ),
                            'type' => 'text',
                            'description' => __( 'The store fax number.' ),
                        ], [
                            'name' => 'ns_store_additional',
                            'value' => ns()->option->get( 'ns_store_additional' ),
                            'label' => __( 'Store Additional Information' ),
                            'type' => 'textarea',
                            'description' => __( 'Store additional information.' ),
                        ], [
                            'name' => 'ns_store_square_logo',
                            'value' => ns()->option->get( 'ns_store_square_logo' ),
                            'label' => __( 'Store Square Logo' ),
                            'type' => 'media',
                            'description' => __( 'Choose what is the square logo of the store.' ),
                        ], [
                            'name' => 'ns_store_rectangle_logo',
                            'value' => ns()->option->get( 'ns_store_rectangle_logo' ),
                            'label' => __( 'Store Rectangle Logo' ),
                            'type' => 'media',
                            'description' => __( 'Choose what is the rectangle logo of the store.' ),
                        ], [
                            'name' => 'ns_store_language',
                            'value' => ns()->option->get( 'ns_store_language' ),
                            'options' => Helper::kvToJsOptions( config( 'nexopos.languages' ) ),
                            'label' => __( 'Language' ),
                            'type' => 'select',
                            'description' => __( 'Define the default fallback language.' ),
                        ], [
                            'name' => 'ns_default_theme',
                            'value' => ns()->option->get( 'ns_default_theme' ),
                            'options' => Helper::kvToJsOptions( [
                                'dark' => __( 'Dark' ),
                                'light' => __( 'Light' ),
                            ] ),
                            'label' => __( 'Theme' ),
                            'type' => 'select',
                            'description' => __( 'Define the default theme.' ),
                        ],
                    ],
                ],
                'currency' => [
                    'label' => __( 'Currency' ),
                    'fields' => [
                        [
                            'name' => 'ns_currency_symbol',
                            'value' => ns()->option->get( 'ns_currency_symbol' ),
                            'label' => __( 'Currency Symbol' ),
                            'type' => 'text',
                            'description' => __( 'This is the currency symbol.' ),
                            'validation' => 'required',
                        ], [
                            'name' => 'ns_currency_iso',
                            'value' => ns()->option->get( 'ns_currency_iso' ),
                            'label' => __( 'Currency ISO' ),
                            'type' => 'text',
                            'description' => __( 'The international currency ISO format.' ),
                            'validation' => 'required',
                        ], [
                            'name' => 'ns_currency_position',
                            'value' => ns()->option->get( 'ns_currency_position' ),
                            'label' => __( 'Currency Position' ),
                            'type' => 'select',
                            'options' => [
                                [
                                    'label' => __( 'Before the amount' ),
                                    'value' => 'before',
                                ], [
                                    'label' => __( 'After the amount' ),
                                    'value' => 'after',
                                ],
                            ],
                            'description' => __( 'Define where the currency should be located.' ),
                        ], [
                            'name' => 'ns_currency_prefered',
                            'value' => ns()->option->get( 'ns_currency_prefered' ),
                            'label' => __( 'Preferred Currency' ),
                            'type' => 'select',
                            'options' => [
                                [
                                    'label' => __( 'ISO Currency' ),
                                    'value' => 'iso',
                                ], [
                                    'label' => __( 'Symbol' ),
                                    'value' => 'symbol',
                                ],
                            ],
                            'description' => __( 'Determine what is the currency indicator that should be used.' ),
                        ], [
                            'name' => 'ns_currency_thousand_separator',
                            'value' => ns()->option->get( 'ns_currency_thousand_separator' ),
                            'label' => __( 'Currency Thousand Separator' ),
                            'type' => 'text',
                            'description' => __( 'Define the symbol that indicate thousand. By default "," is used.' ),
                        ], [
                            'name' => 'ns_currency_decimal_separator',
                            'value' => ns()->option->get( 'ns_currency_decimal_separator' ),
                            'label' => __( 'Currency Decimal Separator' ),
                            'type' => 'text',
                            'description' => __( 'Define the symbol that indicate decimal number. By default "." is used.' ),
                        ], [
                            'name' => 'ns_currency_precision',
                            'value' => ns()->option->get( 'ns_currency_precision', '0' ),
                            'label' => __( 'Currency Precision' ),
                            'type' => 'select',
                            'options' => collect( [0, 1, 2, 3, 4, 5] )->map( function ( $index ) {
                                return [
                                    'label' => sprintf( __( '%s numbers after the decimal' ), $index ),
                                    'value' => $index,
                                ];
                            } )->toArray(),
                            'description' => __( 'Define where the currency should be located.' ),
                        ],
                    ],
                ],
                'date' => [
                    'label' => __( 'Date' ),
                    'fields' => [
                        [
                            'label' => __( 'Date Format' ),
                            'name' => 'ns_date_format',
                            'value' => ns()->option->get( 'ns_date_format' ),
                            'type' => 'select',
                            'options' => Helper::kvToJsOptions( [
                                'Y-m-d' => ns()->date->format( 'Y-m-d' ),
                                'Y/m/d' => ns()->date->format( 'Y/m/d' ),
                                'd-m-y' => ns()->date->format( 'd-m-Y' ),
                                'd/m/y' => ns()->date->format( 'd/m/Y' ),
                                'M dS, Y' => ns()->date->format( 'M dS, Y' ),
                                'd M Y' => ns()->date->format( 'd M Y' ),
                                'd.m.Y' => ns()->date->format( 'd.m.Y' ),
                            ] ),
                            'description' => __( 'This define how the date should be defined. The default format is "Y-m-d".' ),
                        ], [
                            'label' => __( 'Date Time Format' ),
                            'name' => 'ns_datetime_format',
                            'value' => ns()->option->get( 'ns_datetime_format' ),
                            'type' => 'select',
                            'options' => Helper::kvToJsOptions( [
                                'Y-m-d H:i' => ns()->date->format( 'Y-m-d H:i' ),
                                'Y/m/d H:i' => ns()->date->format( 'Y/m/d H:i' ),
                                'd-m-y H:i' => ns()->date->format( 'd-m-Y H:i' ),
                                'd/m/y H:i' => ns()->date->format( 'd/m/Y H:i' ),
                                'M dS, Y H:i' => ns()->date->format( 'M dS, Y H:i' ),
                                'd M Y, H:i' => ns()->date->format( 'd M Y, H:i' ),
                                'd.m.Y, H:i' => ns()->date->format( 'd.m.Y, H:i' ),
                            ] ),
                            'description' => __( 'This define how the date and times hould be formated. The default format is "Y-m-d H:i".' ),
                        ], [
                            'label' => sprintf( __( 'Date TimeZone' ) ),
                            'name' => 'ns_datetime_timezone',
                            'value' => ns()->option->get( 'ns_datetime_timezone' ),
                            'type' => 'search-select',
                            'options' => Helper::kvToJsOptions( config( 'nexopos.timezones' ) ),
                            'description' => sprintf( __( 'Determine the default timezone of the store. Current Time: %s' ), ns()->date->getNowFormatted() ),
                        ],
                    ],
                ],
                'registration' => [
                    'label' => __( 'Registration' ),
                    'fields' => [
                        [
                            'name' => 'ns_registration_enabled',
                            'value' => ns()->option->get( 'ns_registration_enabled' ),
                            'options' => Helper::kvToJsOptions( [
                                'yes' => __( 'Yes' ),
                                'no' => __( 'No' ),
                            ] ),
                            'label' => __( 'Registration Open' ),
                            'type' => 'select',
                            'description' => __( 'Determine if everyone can register.' ),
                        ], [
                            'name' => 'ns_registration_role',
                            'value' => ns()->option->get( 'ns_registration_role' ),
                            'options' => Helper::toJsOptions( Hook::filter( 'ns-registration-roles', Role::get() ), [ 'id', 'name' ] ),
                            'label' => __( 'Registration Role' ),
                            'type' => 'select',
                            'description' => __( 'Select what is the registration role.' ),
                        ], [
                            'name' => 'ns_registration_validated',
                            'value' => ns()->option->get( 'ns_registration_validated' ),
                            'options' => Helper::kvToJsOptions( [
                                'yes' => __( 'Yes' ),
                                'no' => __( 'No' ),
                            ] ),
                            'label' => __( 'Requires Validation' ),
                            'type' => 'select',
                            'description' => __( 'Force account validation after the registration.' ),
                        ], [
                            'name' => 'ns_recovery_enabled',
                            'value' => ns()->option->get( 'ns_recovery_enabled' ),
                            'options' => Helper::kvToJsOptions( [
                                'yes' => __( 'Yes' ),
                                'no' => __( 'No' ),
                            ] ),
                            'label' => __( 'Allow Recovery' ),
                            'type' => 'switch',
                            'description' => __( 'Allow any user to recover his account.' ),
                        ],
                    ],
                ],
            ],
        ];
    }
}
