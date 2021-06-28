<?php
namespace App\Forms;

use App\Classes\Hook;
use App\Http\Requests\UserProfileRequest;
use App\Models\Role;
use App\Models\User;
use App\Models\UserAttribute;
use App\Services\Helper;
use App\Services\Options;
use App\Services\UserOptions;
use App\Services\SettingsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserProfileForm extends SettingsPage
{
    public function __construct()
    {
        $options    =   app()->make( UserOptions::class );
        
        $this->form    =   [
            'tabs'  =>  Hook::filter( 'ns-user-profile-form', [
                'attribute'     =>  include( dirname( __FILE__ ) . '/user-profile/attribute.php' ),
                'security'      =>  include( dirname( __FILE__ ) . '/user-profile/security.php' ),
            ])
        ];
    }

    public function saveForm( Request $request )
    {
        ns()->restrict([ 'manage.profile' ]);

        $validator      =   Validator::make( $request->input( 'security' ), []);

        $results        =   [];       
        $results[]      =   $this->processCredentials( $request, $validator );
        $results[]      =   $this->processOptions( $request );
        $results[]      =   $this->processAttribute( $request );
        $results        =   collect( $results )->filter( fn( $result ) => ! empty( $result ) )->values();

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The profile has been successfully saved.' ),
            'data'      =>  compact( 'results', 'validator' ),
        ];
    }

    public function processAttribute( $request )
    {
        $allowedInputs  =   collect( $this->form[ 'tabs' ][ 'attribute' ][ 'fields' ] )
            ->map( fn( $field ) => $field[ 'name' ] )
            ->toArray();

        if ( ! empty( $allowedInputs ) ) {
            $user           =   UserAttribute::where( 'user_id', Auth::user()->id )->firstOrNew();
            $user->user_id  =   Auth::id();
    
            foreach( $request->input( 'attribute' ) as $key => $value ) {
                if ( in_array( $key, $allowedInputs ) ) {
                    $user->$key     =   strip_tags( $value );
                }
            }
    
            $user->save();
    
            return [
                'status'    =>  'success',
                'message'   =>  __( 'The user attribute has been saved.' )
            ];
        }

        return [];
    }

    public function processOptions( $request )
    {
        /**
         * @var UserOptions
         */
        $userOptions    =   app()->make( UserOptions::class );

        if ( $request->input( 'options' ) ) {
            foreach( $request->input( 'options' ) as $field => $value ) {
                if ( ! in_array( $field, [ 'password', 'old_password', 'password_confirm' ] ) ) {
                    if ( empty( $value ) ) {
                        $userOptions->delete( $field );
                    } else {
                        $userOptions->set( $field, $value );
                    }
                }
            }
    
            return [
                'status'    =>  'success',
                'message'   =>  __( 'The options has been successfully updated.' )
            ];
        }

        return [];
    }

    public function processCredentials( $request, $validator )
    {
        if ( ! empty( $request->input( 'security.old_password' ) ) ) {

            if ( ! Hash::check( $request->input( 'security.old_password' ), Auth::user()->password ) ) {  
    
                $validator->errors()->add( 'security.old_password', __( 'Wrong password provided' ) );
    
                return  [
                    'status'    =>  'failed',
                    'message'   =>  __( 'Wrong old password provided' )
                ];
    
            } else {
                $user               =   User::find( Auth::id() );
                $user->password     =   Hash::make( $request->input( 'security.password'  ) );
                $user->save();
    
                return [
                    'status'    =>  'success',
                    'message'   =>  __( 'Password Successfully updated.' )
                ];
            }
        }
        return [];
    }
}