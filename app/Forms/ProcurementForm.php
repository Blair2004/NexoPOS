<?php
namespace App\Forms;

use App\Services\SettingsPage;

class ProcurementForm extends SettingsPage
{
    public function __construct()
    {
        $options    =   app()->make( UserOptions::class );
        
        $this->form    =   [
            'tabs'  =>  [
                'general'       =>  include( dirname( __FILE__ ) . '/procurement/general.php' ),
            ]
        ];
    }
}