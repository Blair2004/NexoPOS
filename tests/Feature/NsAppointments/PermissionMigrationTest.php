<?php

namespace Tests\Feature\NsAppointments;

use App\Classes\Schema as NsSchema;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PermissionMigrationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $databasePath = dirname( __DIR__, 3 ) . '/tests/database.sqlite';

        if ( ! file_exists( $databasePath ) ) {
            touch( $databasePath );
        }

        $database = new \PDO( 'sqlite:' . $databasePath );
        $database->exec( 'create table if not exists ns_nexopos_options (id integer primary key autoincrement, user_id integer null, key varchar(255) not null, value text null, expire_on datetime null, array tinyint(1) not null default 0, created_at datetime null, updated_at datetime null)' );
        $database->exec( 'create table if not exists ns_nexopos_permissions (id integer primary key autoincrement, name varchar(255) not null unique, namespace varchar(255) not null unique, description text not null, created_at datetime null, updated_at datetime null)' );
        $database->exec( 'create table if not exists ns_nexopos_modules_migrations (id integer primary key autoincrement, namespace varchar(255) not null, file varchar(255) not null)' );
        $database->exec( 'create table if not exists ns_nexopos_transactions (id integer primary key autoincrement, recurring tinyint(1) not null default 0, active tinyint(1) not null default 0, occurrence varchar(255) null)' );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureCoreTablesExist();

        $appointmentPermissionIds = Permission::where( function ( $query ): void {
            $query->where( 'namespace', 'like', 'ns.appointments.%' )
                ->orWhere( 'namespace', 'legacy.appointments.read' );
        } )->pluck( 'id' );

        DB::table( 'nexopos_role_permission' )
            ->whereIn( 'permission_id', $appointmentPermissionIds )
            ->delete();

        Permission::where( function ( $query ): void {
            $query->where( 'namespace', 'like', 'ns.appointments.%' )
                ->orWhere( 'namespace', 'legacy.appointments.read' );
        } )->delete();
    }

    public function test_permissions_are_created_only_when_missing_and_assigned_to_existing_roles(): void
    {
        $existingPermission = $this->createPermission(
            'ns.appointments.read',
            'Custom appointment read',
            'Existing description'
        );

        $admin = $this->createRole( Role::ADMIN, 'Administrator' );
        $storeAdmin = $this->createRole( Role::STOREADMIN, 'Store administrator' );

        $migration = require base_path( 'modules/NsAppointments/Migrations/2026_07_22_000001_create_nsappointments_permissions.php' );

        $migration->up();
        $migration->up();

        $existingPermission->refresh();

        $this->assertSame( 'Custom appointment read', $existingPermission->name );
        $this->assertSame( 'Existing description', $existingPermission->description );
        $this->assertSame( 8, Permission::where( 'namespace', 'like', 'ns.appointments.%' )->count() );

        $this->assertSame( 8, DB::table( 'nexopos_role_permission' )->where( 'role_id', $admin->id )->count() );
        $this->assertSame( 8, DB::table( 'nexopos_role_permission' )->where( 'role_id', $storeAdmin->id )->count() );
    }

    public function test_permission_creation_keeps_name_and_namespace_unique(): void
    {
        $this->createPermission(
            'legacy.appointments.read',
            'Appointments: View appointments',
            'Existing permission using the default name'
        );

        $migration = require base_path( 'modules/NsAppointments/Migrations/2026_07_22_000001_create_nsappointments_permissions.php' );

        $migration->up();

        $permission = Permission::namespace( 'ns.appointments.read' );

        $this->assertInstanceOf( Permission::class, $permission );
        $this->assertSame( 'Appointments: View appointments [ns.appointments.read]', $permission->name );
        $this->assertSame( 1, Permission::where( 'name', 'Appointments: View appointments' )->count() );
        $this->assertSame( 1, Permission::where( 'namespace', 'ns.appointments.read' )->count() );
    }

    private function createRole( string $namespace, string $name ): Role
    {
        $role = Role::namespace( $namespace );

        if ( ! $role instanceof Role ) {
            $role = new Role;
            $role->namespace = $namespace;
            $role->name = $name;
            $role->description = $name;
            $role->save();
        }

        return $role;
    }

    private function createPermission( string $namespace, string $name, string $description ): Permission
    {
        $permission = Permission::where( 'namespace', $namespace )->first();

        if ( ! $permission instanceof Permission ) {
            $permission = new Permission;
            $permission->namespace = $namespace;
            $permission->name = $this->uniquePermissionName( $name, $namespace );
            $permission->description = $description;
            $permission->save();

            return $permission;
        }

        $permission->name = $name;
        $permission->description = $description;
        $permission->save();

        return $permission;
    }

    private function uniquePermissionName( string $name, string $namespace ): string
    {
        return Permission::where( 'name', $name )->exists()
            ? $name . ' [' . $namespace . ']'
            : $name;
    }

    private function ensureCoreTablesExist(): void
    {
        NsSchema::createIfMissing( 'nexopos_permissions', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'name' )->unique();
            $table->string( 'namespace' )->unique();
            $table->text( 'description' );
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_roles', function ( Blueprint $table ): void {
            $table->increments( 'id' );
            $table->string( 'name' )->unique();
            $table->string( 'namespace' )->unique();
            $table->text( 'description' )->nullable();
            $table->integer( 'reward_system_id' )->nullable();
            $table->float( 'minimal_credit_payment' )->default( 0 );
            $table->integer( 'author_id' )->nullable();
            $table->boolean( 'locked' )->default( true );
            $table->timestamps();
        } );

        NsSchema::createIfMissing( 'nexopos_role_permission', function ( Blueprint $table ): void {
            $table->integer( 'permission_id' );
            $table->integer( 'role_id' );
            $table->primary( ['permission_id', 'role_id'], 'ns_appt_role_perm_pk' );
        } );
    }
}
