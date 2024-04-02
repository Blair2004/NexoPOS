<?php

namespace App\Forms;

use App\Services\SettingsPage;

class POSAddressesForm extends SettingsPage
{
    const IDENTIFIER = 'ns.pos-addresses';

    protected $form;

    public function __construct()
    {
        $this->form = [
            'tabs' => [
                'general' => include ( dirname( __FILE__ ) . '/pos/general.php' ),
                'billing' => include ( dirname( __FILE__ ) . '/pos/billing.php' ),
                'shipping' => include ( dirname( __FILE__ ) . '/pos/shipping.php' ),
            ],
        ];
    }
}
