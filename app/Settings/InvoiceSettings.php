<?php

namespace App\Settings;

use App\Services\SettingsPage;

class InvoiceSettings extends SettingsPage
{
    const IDENTIFIER = 'invoices';

    const AUTOLOAD = true;

    public function __construct()
    {
        $this->form = [
            'title' => __( 'Invoice Settings' ),
            'description' => __( 'Configure how invoice and receipts are used.' ),
            'tabs' => [
                'receipts' => include ( dirname( __FILE__ ) . '/invoice-settings/receipts.php' ),
            ],
        ];
    }
}
