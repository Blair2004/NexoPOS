<?php
namespace App\Settings;

use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\SettingsPage;

class ReportsSettings extends SettingsPage
{
    protected $identifier   =   'ns.reports';

    public function __construct()
    {
        $this->form    =   [
            'tabs'  =>  [
                'general'    =>  include( dirname( __FILE__ ) . '/reports/general.php' ),
            ]
        ];
    }
}