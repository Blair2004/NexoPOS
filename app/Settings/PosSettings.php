<?php
namespace App\Settings;

use App\Classes\Hook;
use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class PosSettings extends SettingsPage
{
    public function __construct()
    {
        $options            =   app()->make( Options::class );

        $posSettingsTabs    =   Hook::filter( 'ns-pos-settings-tabs', [
            'layout'    =>  include( dirname( __FILE__ ) . '/pos/layout.php' ),
            'printing'  =>  include( dirname( __FILE__ ) . '/pos/printing.php' ),
            'registers' =>  include( dirname( __FILE__ ) . '/pos/registers.php' ),
            'vat'       =>  include( dirname( __FILE__ ) . '/pos/vat.php' ),
            'shortcuts' =>  include( dirname( __FILE__ ) . '/pos/shortcuts.php' ),
            'features'  =>  include( dirname( __FILE__ ) . '/pos/features.php' ),
        ]);
        
        $this->form    =   [
            'tabs'  =>  $posSettingsTabs
        ];
    }
}