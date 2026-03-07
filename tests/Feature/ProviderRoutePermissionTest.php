<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProviderRouteTest;

/**
 * Proves that provider API routes are not currently protected by
 * NsRestrictMiddleware and should return 403 for users who lack the
 * relevant permissions (nexopos.{create|read|update|delete}.providers).
 *
 * Run order:
 *   1. Run this test BEFORE applying NsRestrictMiddleware — every assertion
 *      will FAIL because the routes accept requests from any authenticated user
 *      regardless of permissions (the vulnerability).
 *   2. Add NsRestrictMiddleware to routes/api/providers.php.
 *   3. Run again — every assertion will PASS, confirming the fix.
 */
class ProviderRoutePermissionTest extends TestCase
{
    use WithAuthentication, WithProviderRouteTest;

    /**
     * Verify that a user without provider permissions receives HTTP 403
     * on every provider API endpoint.
     *
     * Before the fix: test FAILS (routes return 200 — vulnerability confirmed).
     * After the fix:  test PASSES (routes return 403 — correctly restricted).
     */
    public function test_provider_routes_are_restricted_to_permitted_users(): void
    {
        $this->attemptAccessProviderRoutesWithoutPermission();
    }
}
