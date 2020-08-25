<?php
namespace App\Settings;

use App\Http\Requests\UserProfileRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Helper;
use App\Services\Options;
use App\Services\UserOptions;
use App\Services\SettingsPage;
use App\Services\UserOptions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserProfileSettings extends SettingsPage
{
    public function __construct()
    {
        $options    =   app()->make( UserOptions::class );
        
        $this->form    =   [
            'tabs'  =>  [
                'general'       =>  include( dirname( __FILE__ ) . '/user-profile/general.php' ),
                'security'      =>  include( dirname( __FILE__ ) . '/user-profile/security.php' ),
            ]
        ];
    }

    public function saveForm( Request $request )
    {
        $result         =   [];
        $userOptions    =   app()->make( UserOptions::class );
        $inputs         =   $this->getPlainData( $request );

        foreach( $inputs as $field => $value ) {
            if ( ! in_array( $field, [ 'password', 'old_password', 'password_confirm' ] ) ) {
                if ( empty( $value ) ) {
                    $userOptions->delete( $field );
                } else {
                    $userOptions->set( $field, $value );
                }
            }
        }

        $validator      =   Validator::make( $request->all(), []);

        if ( ! Auth::attempt([
            'username'  =>  Auth::user()->username,
            'password'  =>  $request->input( 'security.old_password' )
        ])) {
            
            $validator->errors()->add( 'security.old_password', __( 'Wrong password provided' ) );

            $result[]   =   [
                'status'    =>  'failed',
                'message'   =>  __( 'Wrong old password provided' )
            ];

        } else {
            $user               =   User::find( Auth::id() );
            $user->password     =   Hash::make( $request->input( 'security.password'  ) );
            $user->save();

            $result[]       =   [
                'status'    =>  'success',
                'message'   =>  __( 'Password Successfully updated.' )
            ];
        }


        return [
            'status'    =>  'success',
            'message'   =>  __( 'The profile has been successfully saved.' ),
            'data'      =>  $result,
            'validator' =>  $validator
        ];
    }
}