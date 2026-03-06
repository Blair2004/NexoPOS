<?php

namespace Tests\Traits;

use App\Models\Role;
use App\Services\UsersService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

trait WithApiRoutePermissionTest
{
    /**
     * Verifies that API routes across all protected resources return 403
     * when accessed by a user who has no matching permissions.
     *
     * Middleware fires before the controller, so even requests targeting
     * non-existent resource IDs will produce 403 (not 404) when the user
     * lacks the required permission.
     */
    protected function attemptAccessProtectedRoutesWithoutPermission(): void
    {
        // Authenticate as admin first to set up session / sanity.
        $this->attemptAuthenticate();

        // Create an unprivileged user (base "user" role — no business permissions).
        Mail::fake();

        $userRole = Role::namespace( Role::USER );

        ns()->option->set( 'ns_registration_role', $userRole->id );

        /** @var UsersService $usersService */
        $usersService = app( UsersService::class );

        $slug = Str::random( 8 );

        $unprivilegedUser = $usersService->setUser( [
            'username' => 'test_noperm_' . $slug,
            'email'    => 'test_noperm_' . $slug . '@test.local',
            'password' => 'password',
            'active'   => true,
            'roles'    => [ $userRole->id ],
        ] )['data']['user'];

        Sanctum::actingAs( $unprivilegedUser, ['*'] );

        // Use a fake ID — middleware runs before the controller, so 403 fires first.
        $fakeId = 999999;

        $routes = $this->getProtectedRoutes( $fakeId );

        foreach ( $routes as $description => $route ) {
            $method = strtolower( $route[0] );
            $uri    = $route[1];
            $body   = $route[2] ?? [];

            $response = $this->withSession( $this->app['session']->all() )
                ->json( $method, $uri, $body );

            $this->assertEquals(
                403,
                $response->getStatusCode(),
                "Route [{$route[0]} {$uri}] returned {$response->getStatusCode()} — expected 403 for unprivileged user — {$description}"
            );
        }

        // Clean up
        $unprivilegedUser->delete();
    }

    /**
     * Return the list of protected routes to check.
     *
     * Each entry: 'description' => [ 'METHOD', 'uri', ?body ]
     */
    protected function getProtectedRoutes( int $fakeId ): array
    {
        return [
            // ── Categories ───────────────────────────────────────
            'categories: list'   => ['GET',    'api/categories'],
            'categories: single' => ['GET',    "api/categories/{$fakeId}"],
            'categories: create' => ['POST',   'api/categories', ['name' => 'Test']],
            'categories: update' => ['PUT',    "api/categories/{$fakeId}", ['name' => 'Test']],
            'categories: delete' => ['DELETE', "api/categories/{$fakeId}"],

            // ── Dashboard ────────────────────────────────────────
            'dashboard: day'            => ['GET', 'api/dashboard/day'],
            'dashboard: best-customers' => ['GET', 'api/dashboard/best-customers'],
            'dashboard: best-cashiers'  => ['GET', 'api/dashboard/best-cashiers'],
            'dashboard: recent-orders'  => ['GET', 'api/dashboard/recent-orders'],
            'dashboard: weeks'          => ['GET', 'api/dashboard/weeks'],

            // ── Orders ───────────────────────────────────────────
            'orders: list'              => ['GET',  'api/orders'],
            'orders: supported-payments' => ['GET', 'api/orders/payments'],
            'orders: create'            => ['POST', 'api/orders', ['type' => ['identifier' => 'takeaway']]],

            // ── Procurements ─────────────────────────────────────
            'procurements: list'        => ['GET',    'api/procurements'],
            'procurements: create'      => ['POST',   'api/procurements', ['name' => 'Test']],
            'procurements: delete'      => ['DELETE', "api/procurements/{$fakeId}"],

            // ── Products ─────────────────────────────────────────
            'products: list'            => ['GET',  'api/products'],
            'products: search'          => ['POST', 'api/products/search', ['search' => 'test']],

            // ── Registers ────────────────────────────────────────
            'registers: list'           => ['GET',  'api/cash-registers'],

            // ── Reports (most are POST) ──────────────────────────
            'reports: sale-report'      => ['POST', 'api/reports/sale-report'],
            'reports: sold-stock'       => ['POST', 'api/reports/sold-stock-report'],
            'reports: products-report'  => ['POST', 'api/reports/products-report'],
            'reports: low-stock'        => ['POST', 'api/reports/low-stock'],
            'reports: stock-report'     => ['POST', 'api/reports/stock-report'],
            'reports: annual-report'    => ['POST', 'api/reports/annual-report'],
            'reports: transactions'     => ['POST', 'api/reports/transactions'],
            'reports: payment-types'    => ['POST', 'api/reports/payment-types'],
            'reports: cashier-report'   => ['GET',  'api/reports/cashier-report'],

            // ── Reset ────────────────────────────────────────────
            'reset: truncate'           => ['POST', 'api/reset', ['mode' => 'wipe_plus_simple']],

            // ── Rewards ──────────────────────────────────────────
            'rewards: rules'            => ['GET',    "api/reward-system/{$fakeId}/rules"],
            'rewards: coupons'          => ['GET',    "api/reward-system/{$fakeId}/coupons"],
            'rewards: create'           => ['POST',   'api/reward-system', ['name' => 'Test']],
            'rewards: update'           => ['PUT',    "api/reward-system/{$fakeId}", ['name' => 'Test']],
            'rewards: delete'           => ['DELETE', "api/reward-system/{$fakeId}"],

            // ── Settings ─────────────────────────────────────────
            'settings: get form'        => ['GET',  'api/settings/ns.general'],
            'settings: save form'       => ['POST', 'api/settings/ns.general', []],

            // ── System ───────────────────────────────────────────
            'system: fix-symbolic-links' => ['GET', 'api/system/fix-symbolic-links'],

            // ── Taxes ────────────────────────────────────────────
            'taxes: list'    => ['GET',    'api/taxes'],
            'taxes: create'  => ['POST',   'api/taxes', ['name' => 'Test']],
            'taxes: update'  => ['PUT',    "api/taxes/{$fakeId}", ['name' => 'Test']],
            'taxes: delete'  => ['DELETE', "api/taxes/{$fakeId}"],

            // ── Transactions ─────────────────────────────────────
            // Note: PUT/DELETE routes with model binding ({transaction}, {account})
            // resolve before middleware, so we test list/create endpoints only.
            'transactions: list'               => ['GET',    'api/transactions'],
            'transactions: create'             => ['POST',   'api/transactions', ['name' => 'Test']],
            'transactions-accounts: list'      => ['GET',    'api/transactions-accounts'],
            'transactions-accounts: create'    => ['POST',   'api/transactions-accounts', ['name' => 'Test']],

            // ── Units ────────────────────────────────────────────
            'units: list'          => ['GET',    'api/units'],
            'units-groups: list'   => ['GET',    'api/units-groups'],
            'units: create'        => ['POST',   'api/units', ['name' => 'Test']],
            'units-groups: create' => ['POST',   'api/units-groups', ['name' => 'Test']],
            'units: update'        => ['PUT',    "api/units/{$fakeId}", ['name' => 'Test']],
            'units-groups: update' => ['PUT',    "api/units-groups/{$fakeId}", ['name' => 'Test']],
            'units: delete'        => ['DELETE', "api/units/{$fakeId}"],
            'units-groups: delete' => ['DELETE', "api/units-groups/{$fakeId}"],

            // ── Users (admin-only management routes) ─────────────
            // Note: {role}/clone uses model binding, so we test list/update only.
            'users: list'          => ['GET', 'api/users'],
            'users: permissions'   => ['GET', 'api/users/permissions'],
            'users: roles list'    => ['GET', 'api/users/roles'],
            'users: roles update'  => ['PUT', 'api/users/roles', ['namespace' => 'user', 'name' => 'User']],
        ];
    }
}
