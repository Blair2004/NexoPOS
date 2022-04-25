<?php
namespace App\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

/**
 * @deprecated
 */
class ServiceProvidersSettings extends SettingsPage
{
    protected $identifier   =   'ns.service-providers';
    
    public function __construct()
    {
        $this->form    =   [
            'tabs'  =>  [
                'emails'    =>  include( dirname( __FILE__ ) . '/service-providers/emails.php' ),
                'sms'       =>  include( dirname( __FILE__ ) . '/service-providers/sms.php' ),
            ]
        ];
    }
}