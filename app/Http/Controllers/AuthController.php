<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers;

use App\Classes\Hook;
use App\Exceptions\NotAllowedException;
use App\Http\Requests\SignInRequest;
use App\Http\Requests\SignUpRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


use Tendoo\Core\Exceptions\CoreException;

use App\Services\Options;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;

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

    public function signOut( Request $request )
    {
        Auth::logout();

        $request->session()->regenerate();
        $request->session()->flush();

        return redirect( route( 'ns.dashboard.home' ) );
    }

    public function updateDatabase()
    {
        return view( 'pages.database-update', [
            'title'     =>  __( 'Database Update' )
        ]);
    }

    public function postSignIn( SignInRequest $request )
    {
        Hook::action( 'ns-login-form', $request );
        
        $attempt        =   Auth::attempt([
            'username'  =>  $request->input( 'username' ),
            'password'  =>  $request->input( 'password' )
        ]);

        if ( request()->expectsJson() ) {
            return $this->handleJsonRequests( $request, $attempt );
        } else {
            return $this->handleNormalRequests( $request, $attempt );
        }
    }

    public function handleNormalRequests( $request, $attempt )
    {
        if ( $attempt ) {
            /**
             * check if the account is authorized to 
             * login
             */
            if ( ! Auth::user()->active ) {
                Auth::logout();
                
                $validator      =   Validator::make( $request->all(), []);
                $validator->errors()->add( 'username', __( 'This account is disabled.' ) );

                return redirect( route( 'ns.login' ) )->withErrors( $validator );
            }

            return redirect()->intended(); 
        }

        $validator      =   Validator::make( $request->all(), []);
        $validator->errors()->add( 'username', __( 'Unable to find record having that username.' ) );
        $validator->errors()->add( 'password', __( 'Unable to find record having that password.' ) );

        return redirect( route( 'ns.login' ) )->withErrors( $validator );
    }

    public function handleJsonRequests( $request, $attempt )
    {
        if ( ! $attempt ) {
            throw new NotAllowedException( __( 'Invalid username or password.' ) );
        }

        if ( ! Auth::user()->active ) {
            Auth::logout();                    
            throw new NotAllowedException( __( 'Unable to login, the provided account is not active.' ) );
        }

        // dd( Session::all() );
        
        return [
            'status'    =>  'success',
            'message'   =>  __( 'You have been successfully connected.' ),
            'data'      =>  [
                'redirectTo'    =>  redirect()->intended()->getTargetUrl() ?? url( '/dashboard' )
            ]
        ];
    }

    /**
     * Process user registration
     * @param SignUpRequest $request
     */
    public function postSignUp( SignUpRequest $request )
    {
        Hook::action( 'ns-register-form', $request );

        $options                    =   app()->make( Options::class );
        $role                       =   $options->get( 'ns_registration_role' );
        $registration_validated     =   $options->get( 'ns_registration_validated', 'yes' );

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

        if ( $request->expectsJson() ) {
            return [
                'status'    =>  'success',
                'message'   =>  $registration_validated === 'no' ? 
                    __( 'Your Account has been successfully creaetd.' ) :
                    __( 'Your Account has been created but requires email validation.' ),
                'data'      =>  [
                    'redirectTo'    =>  route( 'ns.login' )
                ]
            ];
        } else {
            return redirect()->route( 'ns.login', [
                'status'    =>  'success',
                'message'   =>  $registration_validated === 'no' ? 
                    __( 'Your Account has been successfully creaetd.' ) :
                    __( 'Your Account has been created but requires email validation.' )
            ]);
        }
    }
}

