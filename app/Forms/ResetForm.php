<?php

namespace App\Forms;

use App\Classes\Hook;
use App\Services\Helper;
use App\Services\SettingsPage;

class ResetForm extends SettingsPage
{
    protected $identifier = 'ns.reset';

    protected $form;

    public function __construct()
    {
        $this->form = [
            'tabs' => [
                'reset' => [
                    'label' => __( 'Reset' ),
                    'fields' => [
                        [
                            'name' => 'mode',
                            'label' => __( 'Mode' ),
                            'validation' => 'required',
                            'type' => 'select',
                            'options' => Helper::kvToJsOptions( Hook::filter( 'ns-reset-options', [
                                'wipe_all' => __( 'Wipe All' ),
                                'wipe_plus_grocery' => __( 'Wipe Plus Grocery' ),
                            ])),
                            'description' => __( 'Choose what mode applies to this demo.' ),
                        ], [
                            'name' => 'create_sales',
                            'label' => __( 'Create Sales (needs Procurements)' ),
                            'type' => 'checkbox',
                            'value' => 1,
                            'description' => __( 'Set if the sales should be created.' ),
                        ], [
                            'name' => 'create_procurements',
                            'label' => __( 'Create Procurements' ),
                            'type' => 'checkbox',
                            'value' => 1,
                            'description' => __( 'Will create procurements.' ),
                        ],
                    ],
                ],
            ],
        ];
    }
}
