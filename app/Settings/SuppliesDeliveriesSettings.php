<?php

namespace App\Settings;

use App\Services\SettingsPage;

class SuppliesDeliveriesSettings extends SettingsPage
{
    const IDENTIFIER = 'supplies-deliveries';

    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = [
            'title' => __( 'Supply Delivery' ),
            'description' => __( 'Configure the delivery feature.' ),
            'tabs' => [
                'layout' => include ( dirname( __FILE__ ) . '/supplies-deliveries/general.php' ),
            ],
        ];
    }
}
