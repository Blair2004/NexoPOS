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
            'tabs' => collect( [
                include ( dirname( __FILE__ ) . '/pos/general.php' ),
                include ( dirname( __FILE__ ) . '/pos/billing.php' ),
                include ( dirname( __FILE__ ) . '/pos/shipping.php' ),
            ] )->mapWithKeys( fn( $tab ) => [
                $tab[ 'identifier' ] => $tab,
            ] )->toArray(),
        ];
    }
}
