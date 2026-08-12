<?php

namespace Tests\Feature;

use App\Filters\MenusFilter;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

final class StoreCashierRegisterPermissionTest extends TestCase
{
    use DatabaseTransactions;

    private const PERMISSION = 'nexopos.read.registers';

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
                'name' => 'Read Registers',
                'description' => 'Let the user read registers.',
            ]
        );

        Permission::query()->firstOrCreate(
            [ 'namespace' => 'read.dashboard' ],
            [
                'name' => 'Read Dashboard',
                'description' => 'Let the user read the dashboard.',
            ]
        );
    }

    public function test_upgrade_grants_register_read_permission_idempotently(): void
    {
        $cashier = Role::namespace( Role::STORECASHIER );
        $permission = Permission::namespace( self::PERMISSION );

        $this->assertInstanceOf( Role::class, $cashier );
        $this->assertInstanceOf( Permission::class, $permission );

        RolePermission::query()
            ->where( 'role_id', $cashier->id )
            ->where( 'permission_id', $permission->id )
            ->delete();

        $migration = require base_path( 'database/migrations/update/2026_08_12_021304_grant_register_read_permission_to_store_cashier.php' );
        $migration->up();
        $migration->up();

        $this->assertSame( 1, RolePermission::query()
            ->where( 'role_id', $cashier->id )
            ->where( 'permission_id', $permission->id )
            ->count() );
    }

    public function test_fresh_cashier_role_definition_grants_register_read_permission(): void
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
    }

    public function test_register_list_menu_requires_read_permission(): void
    {
        $previousSetting = ns()->option->get( 'ns_pos_registers_enabled', 'no' );
        ns()->option->set( 'ns_pos_registers_enabled', 'yes' );

        try {
            $menus = MenusFilter::injectRegisterMenus( [
                'pos' => [
                    'label' => 'POS',
                ],
            ] );

            $this->assertSame(
                [ self::PERMISSION ],
                $menus['registers']['childrens']['list']['permissions']
            );
            $this->assertSame(
                [ 'nexopos.create.registers' ],
                $menus['registers']['childrens']['create']['permissions']
            );
        } finally {
            ns()->option->set( 'ns_pos_registers_enabled', $previousSetting );
        }
    }
}
