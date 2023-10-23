<?php

namespace App\Settings;

use App\Services\Options;
use App\Services\SettingsPage;

class CustomersSettings extends SettingsPage
{
    const IDENTIFIER = 'ns.customers';

    const AUTOLOAD = true;

    public function __construct()
    {
        $options = app()->make( Options::class );

        $this->form = [
            'tabs' => [
                'general' => include( dirname( __FILE__ ) . '/customers/general.php' ),
            ],
        ];
    }
}
