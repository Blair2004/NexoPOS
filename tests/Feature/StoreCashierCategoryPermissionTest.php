<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class StoreCashierCategoryPermissionTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSION = 'nexopos.read.categories';

    protected function setUp(): void
    {
        parent::setUp();

        Role::query()->firstOrCreate(
            [ 'namespace' => Role::STORECASHIER ],
            [
                'name' => 'Store Cashier',
                'description' => 'Has control over the sale process.',
            ]
        );

        Permission::query()->firstOrCreate(
            [ 'namespace' => self::PERMISSION ],
            [
                'name' => 'Read Categories',
                'description' => 'Let the user read categories.',
            ]
        );

        foreach ( [
            'nexopos.create.categories',
            'nexopos.update.categories',
            'nexopos.delete.categories',
        ] as $namespace ) {
            Permission::query()->firstOrCreate(
                [ 'namespace' => $namespace ],
                [ 'name' => $namespace, 'description' => $namespace ]
            );
        }

        Permission::query()->firstOrCreate(
            [ 'namespace' => 'read.dashboard' ],
            [
                'name' => 'Read Dashboard',
                'description' => 'Let the user read the dashboard.',
            ]
        );
    }

    public function test_upgrade_grants_category_read_permission_idempotently(): void
    {
        $cashier = Role::namespace( Role::STORECASHIER );
        $permission = Permission::namespace( self::PERMISSION );

        $this->assertInstanceOf( Role::class, $cashier );
        $this->assertInstanceOf( Permission::class, $permission );

        RolePermission::query()
            ->where( 'role_id', $cashier->id )
            ->where( 'permission_id', $permission->id )
            ->delete();

        $migration = require base_path( 'database/migrations/update/2026_08_12_023752_grant_category_read_permission_to_store_cashier.php' );
        $migration->up();
        $migration->up();

        $this->assertSame( 1, RolePermission::query()
            ->where( 'role_id', $cashier->id )
            ->where( 'permission_id', $permission->id )
            ->count() );
    }

    public function test_fresh_cashier_role_definition_grants_category_read_permission(): void
    {
        $cashier = Role::namespace( Role::STORECASHIER );
        $permission = Permission::namespace( self::PERMISSION );

        $this->assertInstanceOf( Role::class, $cashier );
        $this->assertInstanceOf( Permission::class, $permission );

        RolePermission::query()
            ->where( 'role_id', $cashier->id )
            ->where( 'permission_id', $permission->id )
            ->delete();

        require base_path( 'database/permissions/store-cashier-role.php' );

        $this->assertTrue( $cashier->permissions()
            ->where( 'namespace', self::PERMISSION )
            ->exists() );

        $this->assertFalse( $cashier->permissions()
            ->whereIn( 'namespace', [
                'nexopos.create.categories',
                'nexopos.update.categories',
                'nexopos.delete.categories',
            ] )
            ->exists() );
    }
}
