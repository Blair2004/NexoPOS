<?php
namespace App\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class AccountingSettings extends SettingsPage
{
    public function __construct()
    {
        $this->namespace    =   'ns.accounting';
        
        $this->form    =   [
            'tabs'  =>  [
                'general'    =>  include( dirname( __FILE__ ) . '/accounting/general.php' ),
            ]
        ];
    }
}