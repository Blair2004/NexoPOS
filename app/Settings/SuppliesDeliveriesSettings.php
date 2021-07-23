<?php
namespace App\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class SuppliesDeliveriesSettings extends SettingsPage
{
    public function __construct()
    {
        $this->form    =   [
            'tabs'  =>  [
                'layout'    =>  include( dirname( __FILE__ ) . '/supplies-deliveries/general.php' ),
            ]
        ];
    }
}