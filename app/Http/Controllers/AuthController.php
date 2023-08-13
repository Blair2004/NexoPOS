<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers;

use App\Classes\Hook;
use App\Events\AfterSuccessfulLoginEvent;
use App\Events\PasswordAfterRecoveredEvent;
use App\Events\UserAfterActivationSuccessfulEvent;
use App\Exceptions\NotAllowedException;
use App\Exceptions\NotFoundException;
use App\Http\Requests\PostNewPasswordRequest;
use App\Http\Requests\PostPasswordLostRequest;
use App\Http\Requests\SignInRequest;
use App\Http\Requests\SignUpRequest;
use App\Mail\ActivateYourAccountMail;
use App\Mail\ResetPasswordMail;
use App\Mail\UserRegisteredMail;
use App\Mail\WelcomeMail;
use App\Models\Role;
use App\Models\User;
use App\Models\UserRoleRelation;
use App\Services\Options;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function signIn()
    {
        return view( Hook::filter( 'ns-views:pages.sign-in', 'pages.auth.sign-in' ), [
            'title' => __( 'Sign In &mdash; NexoPOS' ),
        ]);
    }

    public function signUp()
    {
        return view( Hook::filter( 'ns-views:pages.sign-up', 'pages.auth.sign-up' ), [
            'title' => __( 'Sign Up &mdash; NexoPOS' ),
        ]);
    }

    public function activateAccount( User $user, $token )
    {
        /**
         * trying to active an already activated
         * account ? Not possible.
         */
        if ( $user->active ) {
            return redirect( ns()->route( 'ns.login' ) )->with( 'errorMessage', __( 'No activation is needed for this account.' ) );
        }

        /**
         * The activation is not valid.
         * let's throw an exception.
         */
        if ( $user->activation_token !== $token || $user->activation_token === null ) {
            return redirect( ns()->route( 'ns.login' ) )->with( 'errorMessage', __( 'Invalid activation token.' ) );
        }

        /**
         * The activationt token has expired. Let's redirect
         * the user to the login page with a message.
         */
        if ( ! ns()->date->lessThan( Carbon::parse( $user->activation_expiration ) ) ) {
            return redirect( ns()->route( 'ns.login' ) )->with( 'errorMessage', __( 'The expiration token has expired.') );
        }

        $user->activation_expiration = null;
        $user->activation_token = null;
        $user->active = true;
        $user->save();

        /**
         * We'll dispatch an event to warn every
         * component that needs to be aware of that.
         */
        UserAfterActivationSuccessfulEvent::dispatch( $user );

        return redirect( ns()->route( 'ns.login' ) )->with( 'message', __( 'Your account is not activated.' ) );
    }

    public function passwordLost()
    {
        return view( 'pages.auth.password-lost', [
            'title' => __( 'Password Lost' ),
        ]);
    }

    public function newPassword( $userId, $token )
    {
        $user = User::find( $userId );

        if ( ! $user->active ) {
            throw new NotAllowedException( __( 'Unable to change a password for a non active user.' ) );
        }

        if ( $user->activation_token !== $token ) {
            throw new NotAllowedException( __( 'Unable to proceed as the token provided is invalid.' ) );
        }

        if ( Carbon::parse( $user->activation_expiration )->lessThan( now() ) ) {
            throw new NotAllowedException( __( 'The token has expired. Please request a new activation token.' ) );
        }

        return view( 'pages.auth.new-password', [
            'title' => __( 'Set New Password' ),
            'user' => $userId,
            'token' => $token,
        ]);
    }

    public function signOut( Request $request )
    {
        Auth::logout();

        $request->session()->flush();
        $request->cookie( 'nexopos_session', null, 0 );

        return redirect( ns()->route( 'ns.dashboard.home' ) );
    }

    public function updateDatabase()
    {
        return view( 'pages.database-update', [
            'title' => __( 'Database Update' ),
        ]);
    }

    public function postSignIn( SignInRequest $request )
    {
        Hook::action( 'ns-login-form', $request );

        $attempt = Auth::attempt([
            'username' => $request->input( 'username' ),
            'password' => $request->input( 'password' ),
        ]);

        if ( $request->expectsJson() ) {
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

                $validator = Validator::make( $request->all(), []);
                $validator->errors()->add( 'username', __( 'This account is disabled.' ) );

                return redirect( ns()->route( 'ns.login' ) )->withErrors( $validator );
            }

            return redirect()->intended( Hook::filter( 'ns-login-redirect' ) );
        }

        $validator = Validator::make( $request->all(), []);
        $validator->errors()->add( 'username', __( 'Unable to find record having that username.' ) );
        $validator->errors()->add( 'password', __( 'Unable to find record having that password.' ) );

        return redirect( ns()->route( 'ns.login' ) )->withErrors( $validator );
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

        $intended = redirect()->intended()->getTargetUrl();

        event( new AfterSuccessfulLoginEvent( Auth::user() ) );

        $data = [
            'status' => 'success',
            'message' => __( 'You have been successfully connected.' ),
            'data' => [
                'redirectTo' => Hook::filter( 'ns-login-redirect',
                    ( $intended ) === url('/') ? ns()->route( 'ns.dashboard.home' ) : $intended,
                    redirect()->intended()->getTargetUrl() ? true : false
                ),
            ],
        ];

        return $data;
    }

    public function postPasswordLost( PostPasswordLostRequest $request )
    {
        $user = User::where( 'email', $request->input( 'email' ) )->first();

        if ( $user instanceof User ) {
            $user->activation_token = Str::random(20);
            $user->activation_expiration = now()->addMinutes(30);
            $user->save();

            Mail::to( $user )
                ->queue( new ResetPasswordMail( $user ) );

            return [
                'status' => 'success',
                'message' => __( 'The recovery email has been send to your inbox.' ),
                'data' => [
                    'redirectTo' => route( 'ns.intermediate', [
                        'route' => 'ns.login',
                        'from' => 'ns.password-lost',
                    ]),
                ],
            ];
        }

        throw new NotFoundException( __( 'Unable to find a record matching your entry.' ) );
    }

    /**
     * Process user registration
     */
    public function postSignUp( SignUpRequest $request )
    {
        Hook::action( 'ns-register-form', $request );

        /**
         * check user existence
         */
        $user = User::where( 'email', $request->input( 'email' ) )->first();
        if ( $user instanceof User ) {
            throw new NotAllowedException( __( 'Unable to register using this email.' ) );
        }

        /**
         * check user existence
         */
        $user = User::where( 'username', $request->input( 'username' ) )->first();
        if ( $user instanceof User ) {
            throw new NotAllowedException( __( 'Unable to register using this username.' ) );
        }

        $defaultRole = Role::namespace( Role::USER )->firstOrFail();

        $options = app()->make( Options::class );
        $role = $options->get( 'ns_registration_role', $defaultRole->id );
        $registration_validated = $options->get( 'ns_registration_validated', 'yes' );

        if ( empty( $role ) ) {
            throw new Exception( __( 'No role has been defined for registration. Please contact the administrators.' ) );
        }

        $user = new User;
        $user->username = $request->input( 'username' );
        $user->email = $request->input( 'email' );
        $user->password = Hash::make( $request->input( 'password' ) );
        $user->activation_token = Str::random(20);
        $user->activation_expiration = now()->addMinutes(30);

        if ( $registration_validated === 'no' ) {
            $user->active = true;
        }

        $user->save();

        /**
         * We'll assign this user to the first relation
         */
        $relation = new UserRoleRelation;
        $relation->user_id = $user->id;
        $relation->role_id = $role;
        $relation->save();

        /**
         * let's try to email the new user with
         * the details regarding his new created account.
         */
        try {
            /**
             * if the account validation is required, we'll
             * send an email to ask the user to validate his account.
             * Otherwise, we'll notify him about his new account.
             */
            if ( $registration_validated === 'no' ) {
                Mail::to( $user->email )
                    ->queue( new WelcomeMail( $user ) );
            } else {
                Mail::to( $user->email )
                    ->queue( new ActivateYourAccountMail( $user ) );
            }

            /**
             * The administrator might be aware
             * of the user having created their account.
             */
            Role::namespace( 'admin' )->users->each( function ( $admin ) use ( $user ) {
                Mail::to( $admin->email )
                    ->queue( new UserRegisteredMail( $admin, $user ) );
            });
        } catch ( Exception $exception ) {
            Log::error( $exception->getMessage() );
        }

        if ( $request->expectsJson() ) {
            return [
                'status' => 'success',
                'message' => $registration_validated === 'no' ?
                    __( 'Your Account has been successfully created.' ) :
                    __( 'Your Account has been created but requires email validation.' ),
                'data' => [
                    'redirectTo' => ns()->route( 'ns.login' ),
                ],
            ];
        } else {
            return redirect()->route( 'ns.login', [
                'status' => 'success',
                'message' => $registration_validated === 'no' ?
                    __( 'Your Account has been successfully created.' ) :
                    __( 'Your Account has been created but requires email validation.' ),
            ]);
        }
    }

    public function postNewPassword( PostNewPasswordRequest $request, $userID, $token )
    {
        $user = User::find( $userID );

        if ( ! $user instanceof User ) {
            throw new NotFoundException( __( 'Unable to find the requested user.' ) );
        }

        if ( ! $user->active ) {
            throw new NotAllowedException( __( 'Unable to submit a new password for a non active user.' ) );
        }

        if ( $user->activation_token !== $token ) {
            throw new NotAllowedException( __( 'Unable to proceed, the provided token is not valid.' ) );
        }

        if ( Carbon::parse( $user->activation_expiration )->lessThan( ns()->date->now() ) ) {
            throw new NotAllowedException( __( 'Unable to proceed, the token has expired.' ) );
        }

        $user->password = Hash::make( $request->input( 'password' ) );
        $user->activation_token = null;
        $user->activation_expiration = now()->toDateTimeString();
        $user->save();

        event( new PasswordAfterRecoveredEvent( $user ) );

        return [
            'status' => 'success',
            'message' => __( 'Your password has been updated.' ),
            'data' => [
                'redirectTo' => route( 'ns.intermediate', [
                    'route' => 'ns.login',
                    'from' => 'ns.password-updated',
                ]),
            ],
        ];
    }
}
