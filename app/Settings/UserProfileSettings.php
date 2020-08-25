<?php
namespace App\Settings;

use App\Http\Requests\UserProfileRequest;
use App\Models\Role;
use App\Services\Helper;
use App\Services\Options;
use App\Services\UserOptions;
use App\Services\SettingsPage;
use Illuminate\Http\Request;

class UserProfileSettings extends SettingsPage
{
    public function __construct()
    {
        $options    =   app()->make( Options::class );
        
        $this->form    =   [
            'tabs'  =>  [
                'general'   =>  include( dirname( __FILE__ ) . '/user-profile/general.php' ),
            ]
        ];
    }

    public function saveForm( Request $request )
    {
        $userOptions    =   app()->make( UserOptions::class );

        dd( $request->all() );
        
        foreach( $request->except( 'password', 'password_confirm' ) as $field => $value ) {
            if ( empty( $value ) ) {
                $userOptions->delete( $field );
            } else {
                $userOptions->set( $field, $value );
            }
        }

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The profile has been successfully saved.' )
        ];
    }
}