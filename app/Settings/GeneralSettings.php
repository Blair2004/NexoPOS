<?php
namespace App\Settings;

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
                            'validation'    =>  'sometimes|min:5'                           
                        ], [
                            'name'  =>  'ns_store_city',
                            'value'          =>  $options->get( 'ns_store_city' ),
                            'label' =>  __( 'Store City' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The actual store city.' ),
                            'validation'    =>  'sometimes|min:5'                           
                        ], [
                            'name'  =>  'ns_store_phone',
                            'value'          =>  $options->get( 'ns_store_phone' ),
                            'label' =>  __( 'Store Phone' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The phone number to reach the store.' ),
                            'validation'    =>  'sometimes|min:5'                           
                        ], [
                            'name'  =>  'ns_store_email',
                            'value'          =>  $options->get( 'ns_store_email' ),
                            'label' =>  __( 'Store Email' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The actual store email. Might be used on invoice or for reports.' ),
                            'validation'    =>  'required|email'                           
                        ], [
                            'name'  =>  'ns_store_pobox',
                            'value'          =>  $options->get( 'ns_store_pobox' ),
                            'label' =>  __( 'Store PO.Box' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The store mail box number.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_store_fax',
                            'value'          =>  $options->get( 'ns_store_fax' ),
                            'label' =>  __( 'Store Fax' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The store fax number.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_store_additional',
                            'value'          =>  $options->get( 'ns_store_additional' ),
                            'label' =>  __( 'Store Additional Information' ), 
                            'type'          =>  'textarea',
                            'description'   =>  __( 'Store additional informations.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'          =>  'ns_store_language',
                            'value'         =>  $options->get( 'ns_store_language' ),
                            'options'         =>  Helper::kvToJsOptions([
                                'en'       =>  __( 'English' ),
                            ]),
                            'label' =>  __( 'Requires Validation' ), 
                            'type'          =>  'select',
                            'description'   =>  __( 'Force account validation after the registration.' ),
                            'validation'    =>  'required'                           
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
                            'label' =>  __( 'Currency ISO' ), 
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
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_currency_thousand_separator',
                            'value'          =>  $options->get( 'ns_currency_thousand_separator' ),
                            'label' =>  __( 'Currency Thousand Separator' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'Define the symbol that indicate thousand. By default "," is used.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_currency_decimal_separator',
                            'value'          =>  $options->get( 'ns_currency_decimal_separator' ),
                            'label' =>  __( 'Currency Decimal Separator' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'Define the symbol that indicate decimal number. By default "." is used.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_currency_precision',
                            'value'          =>  $options->get( 'ns_currency_precision' ),
                            'label' =>  __( 'Currency Precision' ), 
                            'type'          =>  'select',
                            'options'       =>  collect([0,1,2,3,4,5])->map( function( $index ) {
                                return [
                                    'label' =>  sprintf( __( '%s numbers after the decimal' ), $index ),
                                    'value' =>  $index,
                                ];
                            })->toArray(),
                            'description'   =>  __( 'Define where the currency should be located.' ),
                            'validation'    =>  'required'                           
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
                            'label'         =>  __( 'Date TimeZone' ),
                            'name'          =>  'ns_datetime_timezone',
                            'value'          =>  $options->get( 'ns_datetime_timezone' ),
                            'type'          =>  'text',
                            'description'   =>  __( 'Determine the default timezone of the store.' ),
                        ]
                    ]
                ],
                'registration'   =>  [
                    'label' =>  __( 'Registration' ),
                    'fields'    =>  [
                        [
                            'name'          =>  'ns_registration_status',
                            'value'         =>  $options->get( 'ns_registration_status' ),
                            'options'         =>  Helper::kvToJsOptions([
                                'yes'       =>  __( 'Yes' ),
                                'no'        =>  __( 'No' )
                            ]),
                            'label' =>  __( 'Registration Open' ), 
                            'type'          =>  'select',
                            'description'   =>  __( 'Determine if everyone can register.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'          =>  'ns_registration_role',
                            'value'         =>  $options->get( 'ns_registration_role' ),
                            'options'         =>  Helper::toJsOptions( Role::get(), [ 'id', 'name' ]),
                            'label'         =>  __( 'Registration Open' ), 
                            'type'          =>  'select',
                            'description'   =>  __( 'Determine if everyone can register.' ),
                            'validation'    =>  'required'                           
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
                            'validation'    =>  'required'                           
                        ], 
                    ]
                ],
            ]
        ];
    }
}