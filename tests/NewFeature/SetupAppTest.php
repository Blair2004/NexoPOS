<?php

namespace Tests\NewFeature;

use App\Models\Role;
use App\Models\User;
use App\Services\Helper;
use App\Services\Options;
use App\Services\ResetService;
use App\Services\SetupService;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class SetupAppTest extends TestCase
{
    use WithAuthentication;

    public function test_installation_state()
    {
        $this->assertFalse( Helper::installed() );

        /**
         * @var ResetService $resetService
         */
        $resetService = app()->make( ResetService::class );
        $resetService->hardReset();

        /**
         * It appears that the Authentication middleware has priority overall.
         * So even if the application is not installed, the Authentication middleware
         * will still be used.
         */
        $response = $this->get( '/dashboard' );
        $response->assertRedirectToRoute( 'ns.login' );

        /**
         * On the sign-in page, the Authentication middleware is not used
         * therefore, we should be redirected to the installation page.
         */
        $response = $this->get( '/sign-in' );
        $response->assertRedirectToRoute( 'ns.do-setup' );
    }

    public function test_configure_app()
    {
        $this->assertFalse( Helper::installed() );

        /**
         * @var SetupService $setupService
         */
        $setupService = app()->make( SetupService::class );
        $setupService->runMigration( [
            'language' => 'en',
            'admin_username' => 'admin',
            'password' => '123456',
            'admin_email' => 'contact@nexopos.com',
            'store_name' => 'NexoPOS',
        ] );

        /**
         * We need to test if a user having the email we provided above was created
         * we need to check if that user role is admin
         * we need to test if the store name is "NexoPOS"
         */
        $user = User::where( 'email', 'contact@nexopos.com' )->first();
        $options = new Options;

        $this->assertTrue( $user instanceof User );
        $this->assertTrue( $options->get( 'ns_store_name' ) === 'NexoPOS', 'The store name wasn\'t loaded on the app options' );

        $user->roles->each( function ( Role $role ) {
            $this->assertTrue( $role->namespace === 'admin' );
        } );
    }
}
