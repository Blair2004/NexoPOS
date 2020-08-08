<?php
namespace App\Settings;

use App\Services\SettingsPage;

class GeneralSettings extends SettingsPage
{
    public function getForm()
    {
        return [
            'tabs'  =>  [
                'identification'   =>  [
                    'label' =>  __( 'Identification' ),
                    'fields'    =>  [
                        [
                            'name'  =>  'ns_store_name',
                            'label' =>  __( 'Store Name' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'This is the store name.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_store_address',
                            'label' =>  __( 'Store Address' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The actual store address.' ),
                            'validation'    =>  'sometimes|min:5'                           
                        ], [
                            'name'  =>  'ns_store_city',
                            'label' =>  __( 'Store City' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The actual store city.' ),
                            'validation'    =>  'sometimes|min:5'                           
                        ], [
                            'name'  =>  'ns_store_phone',
                            'label' =>  __( 'Store Phone' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The phone number to reach the store.' ),
                            'validation'    =>  'sometimes|min:5'                           
                        ], [
                            'name'  =>  'ns_store_email',
                            'label' =>  __( 'Store Email' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The actual store email. Might be used on invoice or for reports.' ),
                            'validation'    =>  'required|email'                           
                        ], [
                            'name'  =>  'ns_store_pobox',
                            'label' =>  __( 'Store PO.Box' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The store mail box number.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_store_fax',
                            'label' =>  __( 'Store Fax' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The store fax number.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_store_additional',
                            'label' =>  __( 'Store Fax' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The store fax number.' ),
                            'validation'    =>  'required'                           
                        ]
                    ]
                ],
                'currency'   =>  [
                    'label' =>  __( 'Currency' ),
                    'fields'    =>  [
                        [
                            'name'  =>  'ns_currency_symbol',
                            'label' =>  __( 'Currency Symbol' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'This is the currency symbol.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_currency_iso',
                            'label' =>  __( 'Currency ISO' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'The international currency ISO format.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_currency_position',
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
                            'label' =>  __( 'Currency Thousand Separator' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'Define the symbol that indicate thousand. By default "," is used.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_currency_decimal_separator',
                            'label' =>  __( 'Currency Decimal Separator' ), 
                            'type'          =>  'text',
                            'description'   =>  __( 'Define the symbol that indicate decimal number. By default "." is used.' ),
                            'validation'    =>  'required'                           
                        ], [
                            'name'  =>  'ns_currency_position',
                            'label' =>  __( 'Currency ISO' ), 
                            'type'          =>  'select',
                            'options'       =>  [
                                collect([0,1,2,3,4,5])->map( function( $index ) {
                                    return [
                                        'label' =>  sprintf( __( '%s numbers after the decimal' ), $index ),
                                        'value' =>  $index,
                                    ];
                                })
                            ],
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
                            'type'          =>  'text',
                            'description'   =>  __( 'This define how the date should be defined. The default format is "Y-m-d".' ),
                        ], [
                            'label'         =>  __( 'Date Format' ),
                            'name'          =>  'ns_datetime_format',
                            'type'          =>  'text',
                            'description'   =>  __( 'This define how the date and times hould be formated. The default format is "Y-m-d H:i".' ),
                        ]
                    ]
                ]
            ]
        ];
    }
}