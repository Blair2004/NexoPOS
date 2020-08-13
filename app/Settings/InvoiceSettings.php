<?php
namespace App\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class InvoiceSettings extends SettingsPage
{
    public function __construct()
    {
        $options    =   app()->make( Options::class );
        
        $this->form    =   [
            'tabs'  =>  [
                'receipts'    =>  include( dirname( __FILE__ ) . '/invoice-settings/receipts.php' ),
            ]
        ];
    }
}