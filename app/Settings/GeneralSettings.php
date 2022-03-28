<?php
namespace App\Settings;

use App\Classes\Hook;
use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class GeneralSettings extends SettingsPage
{
    public function __construct()
    {
        $options    =   app()->make( Options::class );
        
        $this->form    =   [
            'tabs'  =>  [
                'identification'   =>  [
                    'label' =>  __( 'Identification' ),
                    'fields'    =>  [
                        [
                            'name'  =>  'ns_store_name',
                            'value'          =>  $options->get( 'ns_store_name' ),
                            'label' =>  __( 'Store Name' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'This is the store name.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_store_address',
                            'value'          =>  $options->get( 'ns_store_address' ),
                            'label' =>  __( 'Store Address' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The actual store address.' ),
                        ], [
                            'name'  =>  'ns_store_city',
                            'value'          =>  $options->get( 'ns_store_city' ),
                            'label' =>  __( 'Store City' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The actual store city.' ),
                        ], [
                            'name'  =>  'ns_store_phone',
                            'value'          =>  $options->get( 'ns_store_phone' ),
                            'label' =>  __( 'Store Phone' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The phone number to reach the store.' ),
                        ], [
                            'name'  =>  'ns_store_email',
                            'value'          =>  $options->get( 'ns_store_email' ),
                            'label' =>  __( 'Store Email' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The actual store email. Might be used on invoice or for reports.' ),
                        ], [
                            'name'  =>  'ns_store_pobox',
                            'value'          =>  $options->get( 'ns_store_pobox' ),
                            'label' =>  __( 'Store PO.Box' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The store mail box number.' ),
                        ], [
                            'name'  =>  'ns_store_fax',
                            'value'          =>  $options->get( 'ns_store_fax' ),
                            'label' =>  __( 'Store Fax' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The store fax number.' ),     
                        ], [
                            'name'  =>  'ns_store_additional',
                            'value'          =>  $options->get( 'ns_store_additional' ),
                            'label' =>  __( 'Store Additional Information' ), 
                            'type'          =>  'textarea',
                            'description'   =>  __( 'Store additional informations.' ),
                        ], [
                            'name'  =>  'ns_store_square_logo',
                            'value'          =>  $options->get( 'ns_store_square_logo' ),
                            'label' =>  __( 'Store Square Logo' ), 
                            'type'          =>  'media',
                            'description'   =>  __( 'Choose what is the square logo of the store.' ),
                        ], [
                            'name'  =>  'ns_store_rectangle_logo',
                            'value'          =>  $options->get( 'ns_store_rectangle_logo' ),
                            'label' =>  __( 'Store Rectangle Logo' ), 
                            'type'          =>  'media',
                            'description'   =>  __( 'Choose what is the rectangle logo of the store.' ),
                        ], [
                            'name'          =>  'ns_store_language',
                            'value'         =>  $options->get( 'ns_store_language' ),
                            'options'         =>  Helper::kvToJsOptions( config( 'nexopos.languages' ) ),
                            'label'         =>  __( 'Language' ), 
                            'type'          =>  'select',
                            'description'   =>  __( 'Define the default fallback language.' ),
                        ], [
                            'name'          =>  'ns_default_theme',
                            'value'         =>  $options->get( 'ns_default_theme' ),
                            'options'         =>  Helper::kvToJsOptions([
                                'dark'      =>  __( 'Dark' ),
                                'light'     =>  __( 'Light' )
                            ]),
                            'label'         =>  __( 'Theme' ), 
                            'type'          =>  'select',
                            'description'   =>  __( 'Define the default theme.' ),
                        ], 
                    ]
                ],
                'currency'   =>  [
                    'label' =>  __( 'Currency' ),
                    'fields'    =>  [
                        [
                            'name'  =>  'ns_currency_symbol',
                            'value'          =>  $options->get( 'ns_currency_symbol' ),
                            'label' =>  __( 'Currency Symbol' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'This is the currency symbol.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_currency_iso',
                            'value'          =>  $options->get( 'ns_currency_iso' ),
                            'label' =>  __( 'Currency ISO' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The international currency ISO format.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_currency_position',
                            'value'          =>  $options->get( 'ns_currency_position' ),
                            'label' =>  __( 'Currency Position' ), 
                            'type'          =>  'select',
                            'options'       =>  [
                                [
                                    'label' =>  __( 'Before the amount' ),
                                    'value' =>  'before',
                                ], [
                                    'label' =>  __( 'After the amount' ),
                                    'value' =>  'after',
                                ]
                            ],
                            'description'   =>  __( 'Define where the currency should be located.' ),
                        ], [
                            'name'  =>  'ns_currency_prefered',
                            'value'          =>  $options->get( 'ns_currency_prefered' ),
                            'label' =>  __( 'Prefered Currency' ), 
                            'type'          =>  'select',
                            'options'       =>  [
                                [
                                    'label' =>  __( 'ISO Currency' ),
                                    'value' =>  'iso',
                                ], [
                                    'label' =>  __( 'Symbol' ),
                                    'value' =>  'symbol',
                                ]
                            ],
                            'description'   =>  __( 'Determine what is the currency indicator that should be used.' ),
                        ], [
                            'name'  =>  'ns_currency_thousand_separator',
                            'value'          =>  $options->get( 'ns_currency_thousand_separator' ),
                            'label' =>  __( 'Currency Thousand Separator' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'Define the symbol that indicate thousand. By default "," is used.' ),
                        ], [
                            'name'  =>  'ns_currency_decimal_separator',
                            'value'          =>  $options->get( 'ns_currency_decimal_separator' ),
                            'label' =>  __( 'Currency Decimal Separator' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'Define the symbol that indicate decimal number. By default "." is used.' ),
                        ], [
                            'name'  =>  'ns_currency_precision',
                            'value'          =>  $options->get( 'ns_currency_precision', '0' ),
                            'label' =>  __( 'Currency Precision' ), 
                            'type'          =>  'select',
                            'options'       =>  collect([0,1,2,3,4,5])->map( function( $index ) {
                                return [
                                    'label' =>  sprintf( __( '%s numbers after the decimal' ), $index ),
                                    'value' =>  $index,
                                ];
                            })->toArray(),
                            'description'   =>  __( 'Define where the currency should be located.' ),
                        ],
                    ]
                ],
                'date'  =>  [
                    'label' =>  __( 'Date' ),
                    'fields'    =>  [
                        [
                            'label'         =>  __( 'Date Format' ),
                            'name'          =>  'ns_date_format',
                            'value'          =>  $options->get( 'ns_date_format' ),
                            'type'          =>  'text',
                            'description'   =>  __( 'This define how the date should be defined. The default format is "Y-m-d".' ),
                        ], [
                            'label'         =>  __( 'Date Format' ),
                            'name'          =>  'ns_datetime_format',
                            'value'          =>  $options->get( 'ns_datetime_format' ),
                            'type'          =>  'text',
                            'description'   =>  __( 'This define how the date and times hould be formated. The default format is "Y-m-d H:i".' ),
                        ], [
                            'label'         =>  sprintf( __( 'Date TimeZone (Now: %s)' ), ns()->date->getNowFormatted() ),
                            'name'          =>  'ns_datetime_timezone',
                            'value'          =>  $options->get( 'ns_datetime_timezone' ),
                            'type'          =>  'select',
                            'options'       =>  Helper::kvToJsOptions( config( 'nexopos.timezones' ) ),
                            'description'   =>  __( 'Determine the default timezone of the store.' ),
                        ]
                    ]
                ],
                'registration'   =>  [
                    'label' =>  __( 'Registration' ),
                    'fields'    =>  [
                        [
                            'name'          =>  'ns_registration_enabled',
                            'value'         =>  $options->get( 'ns_registration_enabled' ),
                            'options'         =>  Helper::kvToJsOptions([
                                'yes'       =>  __( 'Yes' ),
                                'no'        =>  __( 'No' )
                            ]),
                            'label' =>  __( 'Registration Open' ), 
                            'type'          =>  'select',
                            'description'   =>  __( 'Determine if everyone can register.' ),
                        ], [
                            'name'          =>  'ns_registration_role',
                            'value'         =>  $options->get( 'ns_registration_role' ),
                            'options'         =>  Helper::toJsOptions( Hook::filter( 'ns-registration-roles', Role::get() ), [ 'id', 'name' ]),
                            'label'         =>  __( 'Registration Role' ), 
                            'type'          =>  'select',
                            'description'   =>  __( 'Select what is the registration role.' ),
                        ], [
                            'name'          =>  'ns_registration_validated',
                            'value'         =>  $options->get( 'ns_registration_validated' ),
                            'options'         =>  Helper::kvToJsOptions([
                                'yes'       =>  __( 'Yes' ),
                                'no'        =>  __( 'No' )
                            ]),
                            'label' =>  __( 'Requires Validation' ), 
                            'type'          =>  'select',
                            'description'   =>  __( 'Force account validation after the registration.' ),
                        ], [
                            'name'          =>  'ns_recovery_enabled',
                            'value'         =>  $options->get( 'ns_recovery_enabled' ),
                            'options'         =>  Helper::kvToJsOptions([
                                'yes'       =>  __( 'Yes' ),
                                'no'        =>  __( 'No' )
                            ]),
                            'label' =>  __( 'Allow Recovery' ), 
                            'type'          =>  'switch',
                            'description'   =>  __( 'Allow any user to recover his account.' ),
                        ], 
                    ]
                ],
            ]
        ];
    }
}