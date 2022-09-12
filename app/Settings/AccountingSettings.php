<?php

namespace App\Settings;

use App\Services\SettingsPage;

class AccountingSettings extends SettingsPage
{
    protected $identifier = 'ns.accounting';

    public function __construct()
    {
        $this->form = [
            'tabs' => [
                'general' => include( dirname( __FILE__ ) . '/accounting/general.php' ),
            ],
        ];
    }
}
