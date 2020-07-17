<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers;

use App\Http\Requests\SignInRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


use Tendoo\Core\Exceptions\CoreException;

use App\Models\ProductCategory;
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
        return view( 'pages.sign-up' );
    }

    public function passwordLost()
    {
        return view( 'pages.password-lost' );
    }

    public function newPassword()
    {
        return view( 'pages.new-password' );
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

        return redirect( route( 'login' ) )->withErrors( $validator );
    }
}

