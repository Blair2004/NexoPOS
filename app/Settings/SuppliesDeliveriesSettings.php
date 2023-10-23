<?php

namespace App\Settings;

use App\Services\SettingsPage;

class SuppliesDeliveriesSettings extends SettingsPage
{
    const IDENTIFIER = 'ns.supplies-deliveries';

    public function __construct()
    {
        $this->form = [
            'tabs' => [
                'layout' => include( dirname( __FILE__ ) . '/supplies-deliveries/general.php' ),
            ],
        ];
    }
}
