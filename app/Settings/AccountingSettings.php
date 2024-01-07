<?php

namespace App\Settings;

use App\Services\SettingsPage;

class AccountingSettings extends SettingsPage
{
    const IDENTIFIER = 'accounting';

    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = [
            'title' => __('Accounting'),
            'description' => __('Configure the accounting feature'),
            'tabs' => [
                'general' => include(dirname(__FILE__) . '/accounting/general.php'),
            ],
        ];
    }
}
