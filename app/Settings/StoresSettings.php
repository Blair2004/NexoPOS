<?php
namespace App\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class StoresSettings extends SettingsPage
{
    protected $identifier   =   'ns.stores';
    
    public function __construct()
    {
        $options    =   app()->make( Options::class );
        
        $this->form    =   [
            'tabs'  =>  [
                'general'    =>  include( dirname( __FILE__ ) . '/stores/general.php' ),
            ]
        ];
    }
}