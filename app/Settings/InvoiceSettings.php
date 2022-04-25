<?php
namespace App\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class InvoiceSettings extends SettingsPage
{
    protected $identifier   =   'ns.invoice-settings';
    
    public function __construct()
    {
        $this->form    =   [
            'tabs'  =>  [
                'receipts'    =>  include( dirname( __FILE__ ) . '/invoice-settings/receipts.php' ),
            ]
        ];
    }
}