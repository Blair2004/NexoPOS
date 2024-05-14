<?php

namespace App\Settings;

use App\Classes\SettingForm;
use App\Services\SettingsPage;

class OrdersSettings extends SettingsPage
{
    const IDENTIFIER = 'orders';

    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = SettingForm::form(
            title: __( 'Orders Settings' ),
            description: __( 'configure settings that applies to orders.' ),
            tabs: include ( dirname( __FILE__ ) . '/orders/general.php' )
        );
    }
}
