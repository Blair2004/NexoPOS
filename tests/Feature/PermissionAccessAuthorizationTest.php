<?php

namespace Tests\Feature;

use App\Models\PermissionAccess;
use App\Models\Role;
use App\Models\RolePermission;
use App\Models\User;
use App\Services\CoreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PermissionAccessAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    private User $cashier;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $migration = require base_path( 'database/migrations/update/2026_11_05_120937_secure_permission_access_authorization.php' );
        $migration->up();
        app( CoreService::class )->registerGatePermissions();
        $this->cashier = $this->createUser();
        $this->cashier->assignRole( Role::STORECASHIER );
        $this->admin = $this->createUser();
        $this->admin->assignRole( Role::ADMIN );
    }

    private function createUser(): User
    {
        $user = User::factory()->make();
        unset( $user->email_verified_at );
        $user->username = $user->email;
        $user->save();

        return $user;
    }

    private function access( User $requester, string $status = PermissionAccess::PENDING ): PermissionAccess
    {
        $access = new PermissionAccess;
        $access->requester_id = $requester->id;
        $access->granter_id = $status === PermissionAccess::PENDING ? 0 : $this->admin->id;
        $access->permission = 'delete.users';
        $access->status = $status;
        $access->expired_at = now()->addMinutes( 5 );
        $access->save();

        return $access;
    }

    public function test_cashier_cannot_enumerate_or_fetch_other_requests_through_crud(): void
    {
        $access = $this->access( $this->admin );
        Sanctum::actingAs( $this->cashier );
        $this->getJson( '/api/crud/ns.permissions-access' )->assertForbidden();
        $this->getJson( '/api/crud/ns.permissions-access/' . $access->id )->assertForbidden();
        $this->postJson( '/api/crud/ns.permissions-access/export' )->assertForbidden();
    }

    public function test_cashier_can_delete_own_pending_request_but_not_another_users(): void
    {
        $own = $this->access( $this->cashier );
        $foreign = $this->access( $this->admin );
        Sanctum::actingAs( $this->cashier );
        $this->deleteJson( '/api/crud/ns.permissions-access/' . $foreign->id )->assertForbidden();
        $this->assertNotNull( $foreign->fresh() );
        $this->deleteJson( '/api/crud/ns.permissions-access/' . $own->id )->assertOk();
        $this->assertNull( $own->fresh() );
    }

    public function test_cashier_can_still_read_own_request_through_the_dedicated_endpoint(): void
    {
        $own = $this->access( $this->cashier );
        $foreign = $this->access( $this->admin );
        Sanctum::actingAs( $this->cashier );
        $this->getJson( '/api/user/access/' . $own->id )->assertOk()->assertJsonPath( 'id', $own->id );
        $this->getJson( '/api/user/access/' . $foreign->id )->assertNotFound();
    }

    #[DataProvider( 'completedStatuses' )]
    public function test_cashier_cannot_delete_completed_requests( string $status ): void
    {
        $access = $this->access( $this->cashier, $status );
        Sanctum::actingAs( $this->cashier );
        $this->deleteJson( '/api/crud/ns.permissions-access/' . $access->id )->assertForbidden();
        $this->assertNotNull( $access->fresh() );
    }

    public static function completedStatuses(): array
    {
        return array_map( fn( string $status ): array => [$status], [
            PermissionAccess::GRANTED, PermissionAccess::DENIED, PermissionAccess::EXPIRED, PermissionAccess::USED,
        ] );
    }

    public function test_explicit_delete_permission_is_required_even_for_own_request(): void
    {
        Role::namespace( Role::STORECASHIER )->removePermissions( 'delete.permissions-access' );
        $access = $this->access( $this->cashier );
        Sanctum::actingAs( $this->cashier );
        $this->deleteJson( '/api/crud/ns.permissions-access/' . $access->id )->assertForbidden();
        $this->assertNotNull( $access->fresh() );
    }

    public function test_bulk_deletion_checks_every_owner_before_deleting_anything(): void
    {
        Role::namespace( Role::STORECASHIER )->addPermissions( 'read.permissions-access' );
        $own = $this->access( $this->cashier );
        $foreign = $this->access( $this->admin );
        Sanctum::actingAs( $this->cashier );
        $this->postJson( '/api/crud/ns.permissions-access/bulk-actions', [
            'action' => 'delete_selected', 'entries' => [$own->id, $foreign->id],
        ] )->assertForbidden();
        $this->assertNotNull( $own->fresh() );
        $this->assertNotNull( $foreign->fresh() );
    }

    #[DataProvider( 'administratorRoles' )]
    public function test_administrators_can_read_and_delete_other_users_completed_requests( string $role ): void
    {
        $user = $this->createUser();
        $user->assignRole( $role );
        $access = $this->access( $this->cashier, PermissionAccess::GRANTED );
        Sanctum::actingAs( $user );
        $this->getJson( '/api/crud/ns.permissions-access' )->assertOk();
        $this->getJson( '/api/crud/ns.permissions-access/' . $access->id )->assertOk();
        $this->postJson( '/api/crud/ns.permissions-access/bulk-actions', [
            'action' => 'delete_selected', 'entries' => [$access->id],
        ] )->assertOk()->assertJsonPath( 'data.success', 1 );
        $this->assertNull( $access->fresh() );
    }

    public static function administratorRoles(): array
    {
        return [[Role::ADMIN], [Role::STOREADMIN]];
    }

    public function test_creation_uses_explicit_permission_and_server_owned_grant_fields(): void
    {
        ns()->option->set( 'ns_pos_action_permission_enabled', 'yes' );
        ns()->option->set( 'ns_pos_action_permission_restricted_features', ['delete.users'] );
        Role::namespace( Role::STORECASHIER )->removePermissions( 'create.permissions-access' );
        Sanctum::actingAs( $this->cashier );
        $payload = [
            'permission' => 'delete.users', 'requester_id' => $this->admin->id,
            'granter_id' => $this->admin->id, 'status' => PermissionAccess::GRANTED,
        ];
        $this->postJson( '/api/users/check-permission', $payload )->assertForbidden();
        $this->assertSame( 0, PermissionAccess::where( 'requester_id', $this->cashier->id )->count() );
        Role::namespace( Role::STORECASHIER )->addPermissions( 'create.permissions-access' );
        Cache::forget( 'ns-all-permissions-' . $this->cashier->id );
        $this->postJson( '/api/users/check-permission', $payload )->assertForbidden()
            ->assertJsonPath( 'data.access.status', PermissionAccess::PENDING )
            ->assertJsonPath( 'data.access.requester_id', $this->cashier->id )
            ->assertJsonPath( 'data.access.granter_id', 0 );
    }

    public function test_approval_requires_update_permission_and_the_underlying_permission(): void
    {
        $access = $this->access( $this->cashier );
        Sanctum::actingAs( $this->cashier );
        $payload = ['permission' => $access->permission];
        $this->postJson( '/api/user/access/' . $access->id, $payload )->assertForbidden();
        $this->assertSame( PermissionAccess::PENDING, $access->fresh()->status );
        Role::namespace( Role::STORECASHIER )->addPermissions( 'update.permissions-access' );
        Cache::forget( 'ns-all-permissions-' . $this->cashier->id );
        $this->postJson( '/api/user/access/' . $access->id, $payload )->assertNotFound();
        $this->assertSame( PermissionAccess::PENDING, $access->fresh()->status );
        Sanctum::actingAs( $this->admin );
        $this->postJson( '/api/user/access/' . $access->id, $payload )->assertOk();
        $this->assertSame( PermissionAccess::GRANTED, $access->fresh()->status );
    }

    public function test_generic_crud_cannot_create_or_update_grants(): void
    {
        $access = $this->access( $this->cashier );
        Sanctum::actingAs( $this->admin );
        $this->postJson( '/api/crud/ns.permissions-access', ['status' => 'granted'] )->assertForbidden();
        $this->putJson( '/api/crud/ns.permissions-access/' . $access->id, ['status' => 'granted'] )->assertForbidden();
        $this->assertSame( PermissionAccess::PENDING, $access->fresh()->status );
    }

    public function test_upgrade_and_fresh_role_defaults_are_idempotent(): void
    {
        foreach ( [ Role::ADMIN, Role::STOREADMIN, Role::STORECASHIER ] as $namespace ) {
            $role = Role::namespace( $namespace );
            RolePermission::where( 'role_id', $role->id )
                ->whereIn( 'permission_id', $role->permissions()->where( 'namespace', 'like', '%.permissions-access' )->pluck( 'id' ) )
                ->delete();
        }

        $migration = require base_path( 'database/migrations/update/2026_11_05_120937_secure_permission_access_authorization.php' );
        $migration->up();
        $migration->up();
        foreach ( ['admin-role', 'store-admin-role', 'store-cashier-role'] as $file ) {
            require base_path( 'database/permissions/' . $file . '.php' );
        }
        foreach ( [ Role::ADMIN, Role::STOREADMIN, Role::STORECASHIER ] as $namespace ) {
            $role = Role::namespace( $namespace );
            $expected = $namespace === Role::STORECASHIER
                ? ['create.permissions-access', 'delete.permissions-access']
                : ['create.permissions-access', 'read.permissions-access', 'update.permissions-access', 'delete.permissions-access', 'delete-all.permissions-access'];
            $this->assertEqualsCanonicalizing( $expected, $role->permissions()->where( 'namespace', 'like', '%.permissions-access' )->pluck( 'namespace' )->all() );
            $this->assertSame( 0, RolePermission::where( 'role_id', $role->id )->select( 'permission_id' )
                ->groupBy( 'permission_id' )->havingRaw( 'COUNT(*) > 1' )->get()->count() );
        }
    }
}
