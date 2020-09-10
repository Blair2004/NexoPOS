<?php
namespace App\Forms;

use App\Services\SettingsPage;
use App\Services\UserOptions;

class ProcurementForm extends SettingsPage
{
    public function __construct()
    {        
        $this->form    =   [
            'main'          =>  [
                'name'      =>  'name',
                'type'      =>  'text',
                'label'     =>  __( 'Procurement Name' ),
                'description'   =>  __( 'Provide a name that will help to identify the procurement.' ),
                'validation'    =>  'required',
            ],
            'tabs'  =>  [
                'general'       =>  include( dirname( __FILE__ ) . '/procurement/general.php' ),
            ]
        ];
    }
}