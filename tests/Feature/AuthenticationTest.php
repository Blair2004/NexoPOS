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
    public function test_can_see_login_page()
    {
        $response = $this->get( '/sign-in' );
        $response->assertStatus( 200 );
    }

    /**
     * Test if the registration for can be seen
     *
     * @return void
     */
    public function test_can_see_registration_page()
    {
        ns()->option->set( 'ns_registration_enabled', 'yes' );
        $response = $this->get( '/sign-up' );
        $response->assertStatus( 200 );
    }

    /**
     * Test if the registration for can be seen
     *
     * @return void
     *
     * @todo
     */
    public function test_can_see_recovery()
    {
        ns()->option->set( 'ns_recovery_enabled', 'yes' );
        $response = $this->get( '/sign-up' );
        $response->assertStatus( 200 );
    }

    /**
     * Test a valid activation token
     * for the defined user.
     *
     * @return void
     *
     * @todo
     */
    public function test_can_see_activate_user()
    {
        /**
         * Step 1: Check for valid token
         */
        $user = User::first();
        $user->active = false;
        $user->activation_token = Str::random( 10 );
        $user->activation_expiration = now()->addDay();
        $user->save();

        $response = $this->get( '/auth/activate/' . $user->id . '/' . $user->activation_token );
        $response->assertRedirect( ns()->route( 'ns.login' ) );

        /**
         * Step 2: Check for invalid token
         */
        $user = User::first();
        $user->active = false;
        $user->activation_token = Str::random( 10 );
        $user->activation_expiration = now()->addDay();
        $user->save();

        $response = $this->get( '/auth/activate/' . $user->id . '/' . Str::random( 10 ) );
        $response->assertRedirect( ns()->route( 'ns.login' ) );

        /**
         * Step 3: Check for expired token
         */
        $user = User::first();
        $user->active = false;
        $user->activation_token = Str::random( 10 );
        $user->activation_expiration = now()->subDays( 5 );
        $user->save();

        $response = $this->get( '/auth/activate/' . $user->id . '/' . $user->activation_token );
        $response->assertRedirect( ns()->route( 'ns.login' ) );

        /**
         * Step 4: Check for active user
         */
        $user = User::first();
        $user->active = true;
        $user->activation_token = Str::random( 10 );
        $user->activation_expiration = now()->subDays( 5 );
        $user->save();

        $response = $this->get( '/auth/activate/' . $user->id . '/' . $user->activation_token );
        $response->assertRedirect( ns()->route( 'ns.login' ) );
    }

    /**
     * Test if the registration for can be seen
     *
     * @return void
     *
     * @todo
     */
    public function test_can_see_password_lost_form()
    {
        ns()->option->set( 'ns_recovery_enabled', 'yes' );
        $response = $this->get( '/password-lost' );
        $response->assertStatus( 200 );
    }

    /**
     * Test if the registration for can be seen
     *
     * @return void
     *
     * @todo
     */
    public function test_can_see_new_password_form()
    {
        ns()->option->set( 'ns_recovery_enabled', 'yes' );

        $user = User::first();
        $user->active = true;
        $user->activation_token = Str::random( 10 );
        $user->activation_expiration = now()->subDays( 5 );
        $user->save();

        $path = '/new-password/' . $user->id . '/' . $user->activation_token;

        $response = $this->get( $path );
        $response->assertSee( 'The token has expired. Please request a new activation token.' );
        $response->assertStatus( 403 );
    }

    public function generateUsername( $minLength = 10 )
    {
        $username = $this->faker->userName();
        while ( strlen( $username ) < $minLength ) {
            $username .= $this->faker->randomLetter();
        }

        return $username;
    }

    private function fakeEmail()
    {
        $email = $this->faker->email();
        $exploded = explode( '@', $email );
        $exploded[0] = $exploded[0] . Str::random( 5 );

        return implode( '@', $exploded );
    }

    private function fakeUsername()
    {
        return $this->faker->userName() . Str::random( 5 );
    }

    public function test_submit_registration_form()
    {
        $password = $this->faker->password( 8 );
        $registration_validated = ns()->option->get( 'ns_registration_validated', 'yes' );

        /**
         * Step 1: test registration with
         * valid informations
         */
        ns()->option->set( 'ns_registration_enabled', 'yes' );

        $username = $this->fakeUsername();
        $email = $this->fakeEmail();

        $response = $this
            ->withSession( [] )
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post(
                '/auth/sign-up', [
                    'username' => $username,
                    'password' => $password,
                    'password_confirm' => $password,
                    'email' => $email,
                ]
            );

        $response->assertRedirect( route( 'ns.login', [
            'status' => 'success',
            'message' => $registration_validated === 'no' ?
                __( 'Your Account has been successfully created.' ) :
                __( 'Your Account has been created but requires email validation.' ),
        ] ) );

        /**
         * Step 1: we'll verify if the user
         * attribute are created after his registration.
         */
        $user = User::where( 'email', $email )->first();

        $this->assertTrue( $user->attribute()->count() > 0, 'The created user doesn\'t have any attribute.' );

        /**
         * Step 2: test with invalid password and email
         * valid informations
         */
        ns()->option->set( 'ns_registration_enabled', 'yes' );

        $signUpDetails = [
            'username' => $this->fakeUsername(),
            'password' => $password,
            'password_confirm' => $password,
            'email' => 'not-a-valid-email',
        ];

        $response = $this
            ->withSession( [] )
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post(
                '/auth/sign-up', $signUpDetails
            );

        $response->assertRedirect( ns()->route( 'ns.register' ) );

        $response->assertSessionHasErrors( [
            'email' => 'The email field must be a valid email address.',
        ] );

        /**
         * Step 3: test with invalid password
         */
        ns()->option->set( 'ns_registration_enabled', 'yes' );

        $signUpDetails = [
            'username' => $this->fakeUsername(),
            'password' => $password,
            'password_confirm' => $password . 'not-the-same',
            'email' => $this->fakeEmail(),
        ];

        $signUpDetails = [
            'username' => $this->fakeUsername(),
            'password' => 'k%9*~aJ+,<%(z6',
            'password_confirm' => 'k%9*~aJ+,<%(z6' . 'not-the-same',
            'email' => $this->fakeEmail(),
        ];

        $response = $this
            ->withSession( [] )
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post(
                '/auth/sign-up', $signUpDetails
            );

        $response->assertRedirect( ns()->route( 'ns.register' ) );
        $response->assertSessionHasErrors( [
            'password_confirm' => 'The password confirm field must match password.',
        ] );
    }

    public function test_submit_password_recovery_form()
    {
        /**
         * Step 1: with recovery enabled
         * we'll launch recovery process
         */
        ns()->option->set( 'ns_recovery_enabled', 'yes' );

        $user = User::first();

        $response = $this
            ->withSession( [] )
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post( '/auth/password-lost', [
                'email' => $user->email,
            ] );

        $response->assertJsonPath( 'data.redirectTo', route( 'ns.intermediate', [
            'route' => 'ns.login',
            'from' => 'ns.password-lost',
        ] ) );

        /**
         * Step 2: with recovery disabled
         * we'll launch recovery process
         */
        ns()->option->set( 'ns_recovery_enabled', 'no' );

        $user = User::first();

        $response = $this
            ->withSession( [] )
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post( '/auth/password-lost', [
                'email' => $user->email,
            ] );

        $response->assertSee( 'The recovery has been explicitly disabled' );
    }

    public function test_submit_login_form()
    {
        /**
         * Step 1: With exact password
         */
        $user = User::first();
        $user->password = Hash::make( 123456 );
        $user->save();

        $response = $this
            ->withSession( [] )
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post( '/auth/sign-in', [
                'username' => $user->username,
                'password' => 123456,
                '_token' => csrf_token(),
            ] );

        $response->assertRedirect( ns()->route( 'ns.welcome' ) );

        /**
         * Step 2: With wrong password
         */
        $user = User::first();
        $user->password = Hash::make( 123456 );
        $user->save();

        $response = $this
            ->withSession( [] )
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post( '/auth/sign-in', [
                'username' => $user->username,
                'password' => 654321,
                '_token' => csrf_token(),
            ] );

        $response->assertRedirect( ns()->route( 'ns.login' ) );
    }

    public function test_submit_new_password_form()
    {
        /**
         * Step 1: Attempt for account in normal condition
         */
        ns()->option->set( 'ns_recovery_enabled', 'yes' );

        $user = User::first();
        $user->active = true;
        $user->activation_token = Str::random( 10 );
        $user->activation_expiration = ns()->date->addDay();
        $user->save();

        // we'll keeping that way as it's a weak password for testing purpose.
        $password = '123456';

        $response = $this
            ->withSession( [] )
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
        ] ) );

        /**
         * Step 2: Attempt for account recovery when it's disabled
         */
        ns()->option->set( 'ns_recovery_enabled', 'no' );

        $user = User::first();
        $user->active = true;
        $user->activation_token = Str::random( 10 );
        $user->activation_expiration = ns()->date->addDay();
        $user->save();

        $password = $this->faker->password();

        $response = $this
            ->withSession( [] )
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
