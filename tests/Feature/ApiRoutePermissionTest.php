<?php

namespace Tests\Feature;

use Tests\TestCase;
use Tests\Traits\WithApiRoutePermissionTest;
use Tests\Traits\WithAuthentication;

/**
 * Verifies that all API routes protected by NsRestrictMiddleware correctly
 * return HTTP 403 when accessed by a user without the required permissions.
 *
 * Covers: categories, dashboard, orders, procurements, products, registers,
 * reports, reset, rewards, settings, system, taxes, transactions, units, users.
 */
class ApiRoutePermissionTest extends TestCase
{
    use WithAuthentication, WithApiRoutePermissionTest;

    /**
     * An unprivileged user must receive 403 on every protected API route.
     */
    public function test_protected_api_routes_are_restricted_to_permitted_users(): void
    {
        $this->attemptAccessProtectedRoutesWithoutPermission();
    }
}
