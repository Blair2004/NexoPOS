<?php

namespace Tests\NewFeature;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use WithFaker;

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

    public function test_can_see_login_page()
    {
        $response = $this->get( '/sign-in' );
        $response->assertStatus( 200 );
    }

    public function test_can_see_registration_page()
    {
        /**
         * Step 1: we'll enable registration and test
         * if the page is accessible.
         */
        ns()->option->set( 'ns_registration_enabled', 'yes' );
        $response = $this->get( '/sign-up' );
        $response->assertStatus( 200 );

        /**
         * Step 2: we'll disable the registration and
         * assert it can't be accessed
         */
        ns()->option->set( 'ns_registration_enabled', 'no' );
        $response = $this->get( '/sign-up' );
        $response->assertStatus( 403 );

        /**
         * Step 3: we'll force submit post data
         * to see wether it the registration is
         * not disabled
         */
        $username = $this->fakeUsername();
        $email = $this->fakeEmail();
        $password = $this->faker->password( 8 );

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

        $response->assertStatus( 403 );
    }

    public function test_can_see_recovery_page()
    {
        /**
         * Step 1: assert when recovery is enabled, the
         * password recovery can be seen
         */
        ns()->option->set( 'ns_recovery_enabled', 'yes' );
        $response = $this->get( '/password-lost' );
        $response->assertStatus( 200 );

        /**
         * Step 2: Assert the recovery page can't be seen
         * when the recovery is disabled
         */
        ns()->option->set( 'ns_recovery_enabled', 'no' );
        $response = $this->get( '/password-lost' );
        $response->assertStatus( 403 );
    }

    public function test_can_verify_account()
    {
        // make sure verification is forced for new users
    }

    public function test_can_submit_recovery_email() {}

    public function test_can_change_password() {}

    public function test_cannot_login_if_account_inactive()
    {
        $password = '123456';
        $user = User::first();
        $user->active = false;
        $user->password = Hash::make( $password );
        $user->save();

        $response = $this
            ->withSession( [] )
            ->withHeader( 'X-CSRF-TOKEN', csrf_token() )
            ->post(
                '/auth/sign-in', [
                    'username' => $user->username,
                    'password' => $password,
                ]
            );

        $response->assertSessionHasErrors( 'username' );
        $response->assertRedirect( route( 'ns.login' ) );
    }
}
