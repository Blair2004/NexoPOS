<?php

namespace App\Settings;

use App\Services\Options;
use App\Services\SettingsPage;

class CustomersSettings extends SettingsPage
{
    protected $identifier = 'ns.customers';

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
