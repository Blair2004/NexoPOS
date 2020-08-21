<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers;

use App\Http\Requests\SignInRequest;
use App\Http\Requests\SignUpRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


use Tendoo\Core\Exceptions\CoreException;

use App\Services\Options;
use App\Models\User;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function signIn()
    {
        return view( 'pages.sign-in', [
            'title'     =>  __( 'Sign In &mdash; NexoPOS' )
        ]);
    }

    public function signUp()
    {
        return view( 'pages.sign-up', [
            'title'     =>      __( 'Sign Up &mdash; NexoPOS' )
        ]);
    }

    public function passwordLost()
    {
        return view( 'pages.password-lost' );
    }

    public function newPassword()
    {
        return view( 'pages.new-password' );
    }

    public function signOut()
    {
        Auth::logout();
        return redirect( route( 'ns.login' ) );
    }

    public function postSignIn( SignInRequest $request )
    {
        $attempt    =   Auth::attempt([
            'username'  =>  $request->input( 'username' ),
            'password'  =>  $request->input( 'password' )
        ]);

        if ( $attempt ) {
            return redirect( route( 'dashboard.index' ) );
        }

        $validator      =   Validator::make( $request->all(), []);
        $validator->errors()->add( 'username', __( 'Unable to find record having that username.' ) );
        $validator->errors()->add( 'password', __( 'Unable to find record having that password.' ) );

        return redirect( route( 'ns.login' ) )->withErrors( $validator );
    }

    /**
     * Process user registration
     * @param SignUpRequest $request
     */
    public function postSignUp( SignUpRequest $request )
    {
        $options                    =   app()->make( Options::class );
        $role                       =   $options->get( 'ns_registration_role' );
        $registration_validated     =   $options->get( 'ns_registration_validated', 'no' );

        if ( empty( $role ) ) {
            throw new Exception( __( 'No role has been define for registration. Please contact the administrators.' ) );
        }

        $user               =   new User;
        $user->username     =   $request->input( 'username' );
        $user->email        =   $request->input( 'email' );
        $user->password     =   Hash::make( $request->input( 'password' ) );
        $user->role_id      =   $role;

        if ( $registration_validated === 'no' ) {
            $user->active   =   true;
        }

        $user->save();

        return redirect()->route( 'ns.login', [
            'status'    =>  'success',
            'message'   =>  $registration_validated === 'no' ? 
                __( 'Your Account has been successfully creaetd.' ) :
                __( 'Your Account has been created but requires email validation.' )
        ]);
    }
}

