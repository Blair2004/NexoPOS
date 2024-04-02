<?php

namespace App\Settings;

use App\Services\SettingsPage;

class ReportsSettings extends SettingsPage
{
    const IDENTIFIER = 'reports';

    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = [
            'title' => __( 'Report Settings' ),
            'description' => __( 'Configure the settings' ),
            'tabs' => [
                'general' => include ( dirname( __FILE__ ) . '/reports/general.php' ),
            ],
        ];
    }
}
