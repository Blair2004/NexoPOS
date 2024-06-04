<?php
namespace Tests\NewFeature;

use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    public function testCanSeeLoginPage()
    {
        $response = $this->get( '/sign-in' );
        $response->assertStatus( 200 );
    }

    public function testCanSeeRegistrationPage()
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
        $response->assertStatus( 401 );
    }

    public function testCanSeeRecoveryPage()
    {
        /**
         * Step 1: assert when recovery is enabled, the 
         * password recovery can be seen
         */
        ns()->option->set( 'ns_recovery_enabled', 'no' );
        $response = $this->get( '/password-lost' );
        $response->assertStatus( 200 );

        /**
         * Step 2: Assert the recovery page can't be seen
         * when the recovery is disabled
         */
        ns()->option->set( 'ns_recovery_enabled', 'no' );
        $response = $this->get( '/password-lost' );
        $response->assertStatus( 200 );
    }

    public function testCannotSeeRegistrationIfDisabled()
    {
        
    }

    public function testCannotSubmitRegistrationIfDisabled()
    {

    }

    public function testCanRegister()
    {

    }

    public function testCanVerifyAccount()
    {

    }

    public function testCanSubmitRecoveryEmail()
    {

    }

    public function testCanChangePassword()
    {

    }

    public function testCannotLoginIfAccountInactive()
    {

    }
}