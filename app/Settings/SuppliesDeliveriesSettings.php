<?php

namespace App\Settings;

use App\Services\SettingsPage;

class SuppliesDeliveriesSettings extends SettingsPage
{
    protected $identifier = 'ns.supplies-deliveries';

    public function __construct()
    {
        $this->form = [
            'tabs' => [
                'layout' => include( dirname( __FILE__ ) . '/supplies-deliveries/general.php' ),
            ],
        ];
    }
}
