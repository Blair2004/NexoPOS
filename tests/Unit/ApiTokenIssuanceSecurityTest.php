<?php

namespace Tests\Unit;

use App\Http\Middleware\NsRestrictMiddleware;
use Tests\TestCase;

class ApiTokenIssuanceSecurityTest extends TestCase
{
    public function test_token_creation_route_requires_profile_management_permission(): void
    {
        $route = collect( app( 'router' )->getRoutes() )
            ->first( fn( $route ) => $route->uri() === 'api/users/create-token' );

        $this->assertNotNull( $route );
        $this->assertContains(
            NsRestrictMiddleware::arguments( 'manage.profile' ),
            $route->gatherMiddleware()
        );
    }
}
