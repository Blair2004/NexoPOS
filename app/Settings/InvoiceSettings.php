<?php

namespace App\Settings;

use App\Services\SettingsPage;

class InvoiceSettings extends SettingsPage
{
    protected $identifier = 'ns.invoice-settings';

    public function __construct()
    {
        $this->form = [
            'tabs' => [
                'receipts' => include( dirname( __FILE__ ) . '/invoice-settings/receipts.php' ),
            ],
        ];
    }
}
