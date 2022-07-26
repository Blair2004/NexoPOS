<?php

namespace App\Settings;

use App\Services\Options;
use App\Services\SettingsPage;

class OrdersSettings extends SettingsPage
{
    protected $identifier = 'ns.orders';

    public function __construct()
    {
        $options = app()->make( Options::class );

        $this->form = [
            'tabs' => [
                'layout' => include( dirname( __FILE__ ) . '/orders/general.php' ),
            ],
        ];
    }
}
