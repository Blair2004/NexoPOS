<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use WithFaker;

    /**
     * Test if we can see the login page
     *
     * @return void
     */
    public function testCanSeeLoginPage()
    {
        $response = $this->get('/sign-in');
        $response->assertStatus(200);
    }

    /**
     * Test if the registration for can be seen
     *
     * @return void
     */
    public function testCanSeeRegistrationPage()
    {
        $response = $this->get('/sign-up');
        $response->assertStatus(200);
    }

    /**
     * Test if the registration for can be seen
     *
     * @return void
     *
     * @todo
     */
    public function testCanSeeRecovery()
    {
        $response = $this->get('/sign-up');
        $response->assertStatus(200);
    }

    /**
     * Test a valid activation token
     * for the defined user.
     *
     * @return void
     *
     * @todo
     */
    public function testCanSeeActivateUser()
    {
        /**
         * Step 1: Check for valid token
         */
        $user = User::first();
        $user->active = false;
        $user->activation_token = Str::random(10);
        $user->activation_expiration = now()->addDay();
        $user->save();

        $response = $this->get('/auth/activate/' . $user->id . '/' . $user->activation_token );
        $response->assertRedirect( ns()->route( 'ns.login' ) );

        /**
         * Step 2: Check for invalid token
         */
        $user = User::first();
        $user->active = false;
        $user->activation_token = Str::random(10);
        $user->activation_expiration = now()->addDay();
        $user->save();

        $response = $this->get('/auth/activate/' . $user->id . '/' . Str::random(10) );
        $response->assertRedirect( ns()->route( 'ns.login' ) );

        /**
         * Step 3: Check for expired token
         */
        $user = User::first();
        $user->active = false;
        $user->activation_token = Str::random(10);
        $user->activation_expiration = now()->subDays(5);
        $user->save();

        $response = $this->get('/auth/activate/' . $user->id . '/' . $user->activation_token );
        $response->assertRedirect( ns()->route( 'ns.login' ) );

        /**
         * Step 4: Check for active user
         */
        $user = User::first();
        $user->active = true;
        $user->activation_token = Str::random(10);
        $user->activation_expiration = now()->subDays(5);
        $user->save();

        $response = $this->get('/auth/activate/' . $user->id . '/' . $user->activation_token );
        $response->assertRedirect( ns()->route( 'ns.login' ) );
    }

    /**
     * Test if the registration for can be seen
     *
     * @return void
     *
     * @todo
     */
    public function testCanSeePasswordLostForm()
    {
        $response = $this->get('/password-lost');
        $response->assertStatus(200);
    }

    /**
     * Test if the registration for can be seen
     *
     * @return void
     *
     * @todo
     */
    public function testCanSeeNewPasswordForm()
    {
        $user = User::first();
        $user->active = true;
        $user->activation_token = Str::random(10);
        $user->activation_expiration = now()->subDays(5);
        $user->save();

        $path = '/new-password/' . $user->id . '/' . $user->activation_token;

        $response = $this->get( $path );
        $response->assertSee( 'The token has expired. Please request a new activation token.' );
        $response->assertStatus(200);
    }

    public function testSubmitRegistrationForm()
    {
        $password = $this->faker->password();
        $registration_validated = ns()->option->get( 'ns_registration_validated', 'yes' );

        /**
         * Step 1: test registration with
         * valid informations
         */
        ns()->option->set( 'ns_registration_enabled', 'yes' );

        $response = $this
            ->withSession([])
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post(
                '/auth/sign-up', [
                    'username' => $this->faker->userName(),
                    'password' => $password,
                    'password_confirm' => $password,
                    'email' => $this->faker->email(),
                ]
            );

        $response->assertRedirect( route( 'ns.login', [
            'status' => 'success',
            'message' => $registration_validated === 'no' ?
                __( 'Your Account has been successfully created.' ) :
                __( 'Your Account has been created but requires email validation.' ),
        ]) );

        /**
         * Step 1: test with invalid password and email
         * valid informations
         */
        ns()->option->set( 'ns_registration_enabled', 'yes' );

        $response = $this
            ->withSession([])
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post(
                '/auth/sign-up', [
                    'username' => $this->faker->userName(),
                    'password' => $password,
                    'password_confirm' => $password . '122',
                    'email' => 'not-a-valid-email',
                ]
            );

        $response->assertSee( 'Unable to proceed, the submitted form is not valid.' );
    }

    public function testSubmitPasswordRecoveryForm()
    {
        /**
         * Step 1: with recovery enabled
         * we'll launch recovery process
         */
        ns()->option->set( 'ns_recovery_enabled', 'yes' );

        $user = User::first();

        $response = $this
            ->withSession([])
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post( '/auth/password-lost', [
                'email' => $user->email,
            ]);

        $response->assertJsonPath( 'data.redirectTo', route( 'ns.intermediate', [
            'route' => 'ns.login',
            'from' => 'ns.password-lost',
        ]) );

        /**
         * Step 2: with recovery disabled
         * we'll launch recovery process
         */
        ns()->option->set( 'ns_recovery_enabled', 'no' );

        $user = User::first();

        $response = $this
            ->withSession([])
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post( '/auth/password-lost', [
                'email' => $user->email,
            ]);

        $response->assertSee( 'The recovery has been explicitly disabled' );
    }

    public function testSubmitLoginForm()
    {
        /**
         * Step 1: With exact password
         */
        $user = User::first();
        $user->password = Hash::make(123456);
        $user->save();

        $response = $this
            ->withSession([])
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post( '/auth/sign-in', [
                'username' => $user->username,
                'password' => 123456,
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect( ns()->route( 'ns.welcome' ) );

        /**
         * Step 2: With wrong password
         */
        $user = User::first();
        $user->password = Hash::make(123456);
        $user->save();

        $response = $this
            ->withSession([])
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post( '/auth/sign-in', [
                'username' => $user->username,
                'password' => 654321,
                '_token' => csrf_token(),
            ]);

        $response->assertRedirect( ns()->route( 'ns.login' ) );
    }

    public function testSubmitNewPasswordForm()
    {
        /**
         * Step 1: Attempt for account in normal condition
         */
        ns()->option->set( 'ns_recovery_enabled', 'yes' );

        $user = User::first();
        $user->active = true;
        $user->activation_token = Str::random(10);
        $user->activation_expiration = ns()->date->addDay();
        $user->save();

        // we'll keeping that way as it's a weak password for testing purpose.
        $password = '123456';

        $response = $this
            ->withSession([])
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post(
                'auth/new-password/' . $user->id . '/' . $user->activation_token, [
                    'password' => $password,
                    'password_confirm' => $password,
                ]
            );

        $response->assertJsonPath( 'data.redirectTo', route( 'ns.intermediate', [
            'route' => 'ns.login',
            'from' => 'ns.password-updated',
        ]) );

        /**
         * Step 2: Attempt for account recovery when it's disabled
         */
        ns()->option->set( 'ns_recovery_enabled', 'no' );

        $user = User::first();
        $user->active = true;
        $user->activation_token = Str::random(10);
        $user->activation_expiration = ns()->date->addDay();
        $user->save();

        $password = $this->faker->password();

        $response = $this
            ->withSession([])
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post(
                'auth/new-password/' . $user->id . '/' . $user->activation_token, [
                    'password' => $password,
                    'password_confirm' => $password,
                ]
            );

        $response->assertSee( 'The recovery has been explicitly disabled.' );
    }
}
