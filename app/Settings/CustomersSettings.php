<?php

namespace App\Settings;

use App\Classes\SettingForm;
use App\Services\SettingsPage;

class CustomersSettings extends SettingsPage
{
    const IDENTIFIER = 'customers';

    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = SettingForm::form(
            title: __( 'Customers Settings' ),
            description: __( 'Configure the customers settings of the application.' ),
            tabs: SettingForm::tabs(
                include dirname( __FILE__ ) . '/customers/general.php' )
        );
    }
}
