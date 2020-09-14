<?php
namespace App\Forms;

use App\Models\Procurement;
use App\Services\SettingsPage;
use App\Services\UserOptions;

class ProcurementForm extends SettingsPage
{
    public function __construct()
    {        
        if ( ! empty( request()->route( 'identifier' ) ) ) {
            $procurement    =   Procurement::with( 'products' )
                ->with( 'provider' )
                ->find( request()->route( 'identifier' ) );
        }

        $this->form    =   [
            'main'          =>  [
                'name'      =>  'name',
                'type'      =>  'text',
                'value'     =>  $procurement->name ?? '',
                'label'     =>  __( 'Procurement Name' ),
                'description'   =>  __( 'Provide a name that will help to identify the procurement.' ),
                'validation'    =>  'required',
            ],
            'products'          =>  $procurement->products ?? [],
            'tabs'              =>  [
                'general'       =>  include( dirname( __FILE__ ) . '/procurement/general.php' ),
            ]
        ];
    }
}