<?php

namespace Tests\Traits;

use App\Models\Role;
use App\Services\UsersService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

trait WithProviderRouteTest
{
    /**
     * Proves that provider API routes are not protected behind
     * NsRestrictMiddleware by asserting every endpoint returns 403
     * when accessed by a user who has no provider permissions.
     *
     * Before the fix:  assertions FAIL — routes return 200 (vulnerability).
     * After the fix:   assertions PASS — routes return 403 (secure).
     */
    protected function attemptAccessProviderRoutesWithoutPermission(): void
    {
        /**
         * Step 1: create a test provider as admin via the CRUD API so we
         * have a valid resource ID to target in the permission checks.
         */
        $this->attemptAuthenticate();

        $createResponse = $this->withSession( $this->app['session']->all() )
            ->json( 'POST', 'api/crud/ns.providers', [
                'first_name' => 'Test Provider ' . Str::random( 5 ),
            ] );

        $createResponse->assertJson( [ 'status' => 'success' ] );

        $providerId = $createResponse->json( 'data.entry.id' );

        $this->assertNotNull( $providerId, 'Provider ID should not be null after creation.' );

        /**
         * Step 2: create a fresh user assigned to the base "user" role,
         * which has no provider permissions, and authenticate as that user.
         *
         * We fake the mailer so registration emails don't cause failures,
         * and we force active=true to skip the validation-token flow.
         */
        Mail::fake();

        $userRole = Role::namespace( Role::USER );

        // setUser() validates ns_registration_role before checking $attributes['roles'],
        // so we must ensure the option points to a real role in this test DB.
        ns()->option->set( 'ns_registration_role', $userRole->id );

        /** @var UsersService $usersService */
        $usersService = app( UsersService::class );

        $slug = Str::random( 8 );

        $unprivilegedUser = $usersService->setUser( [
            'username' => 'test_noperm_' . $slug,
            'email' => 'test_noperm_' . $slug . '@test.local',
            'password' => 'password',
            'active' => true,
            'roles' => [ $userRole->id ],
        ] )[ 'data' ][ 'user' ];

        Sanctum::actingAs( $unprivilegedUser, [ '*' ] );

        /**
         * GET /api/providers — list all providers
         * Requires: nexopos.read.providers
         */
        $this->withSession( $this->app['session']->all() )
            ->json( 'GET', 'api/providers' )
            ->assertStatus( 403 );

        /**
         * GET /api/providers/{id}/procurements — list procurements for a provider
         * Requires: nexopos.read.providers
         */
        $this->withSession( $this->app['session']->all() )
            ->json( 'GET', 'api/providers/' . $providerId . '/procurements' )
            ->assertStatus( 403 );

        /**
         * DELETE /api/providers/{id} — delete a provider
         * Requires: nexopos.delete.providers
         */
        $this->withSession( $this->app['session']->all() )
            ->json( 'DELETE', 'api/providers/' . $providerId )
            ->assertStatus( 403 );

        /**
         * Step 3: clean up — delete the test provider and the temporary
         * user to leave the DB in a clean state.
         */
        $this->attemptAuthenticate();

        $this->withSession( $this->app['session']->all() )
            ->json( 'DELETE', 'api/crud/ns.providers/' . $providerId );

        $unprivilegedUser->delete();
    }
}
