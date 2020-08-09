<?php
namespace App\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class PosSettings extends SettingsPage
{
    public function __construct()
    {
        $options    =   app()->make( Options::class );
        
        $this->form    =   [
            'tabs'  =>  [
                'layout'    =>  include_once( dirname( __FILE__ ) . '/pos/layout.php' ),
                'registers' =>  include_once( dirname( __FILE__ ) . '/pos/registers.php' ),
                'vat'       =>  include_once( dirname( __FILE__ ) . '/pos/vat.php' )
            ]
        ];
    }
}