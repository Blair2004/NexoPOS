<?php

namespace App\Settings;

use App\Services\Options;
use App\Services\SettingsPage;

class OrdersSettings extends SettingsPage
{
    const IDENTIFIER = 'ns.orders';

    const AUTOLOAD  =   true;

    public function __construct()
    {
        $this->labels   =   [
            'title' =>  __( 'Orders Settings' ),
            'description'   =>  __( 'configure settings that applies to orders.' )
        ];

        $options = app()->make( Options::class );

        $this->form = [
            'tabs' => [
                'layout' => include( dirname( __FILE__ ) . '/orders/general.php' ),
            ],
        ];
    }
}
