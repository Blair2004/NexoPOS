<?php
namespace App\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class ServiceProvidersSettings extends SettingsPage
{
    public function __construct()
    {
        $options    =   app()->make( Options::class );
        
        $this->form    =   [
            'tabs'  =>  [
                'emails'    =>  include( dirname( __FILE__ ) . '/service-providers/emails.php' ),
                'sms'       =>  include( dirname( __FILE__ ) . '/service-providers/sms.php' ),
            ]
        ];
    }
}