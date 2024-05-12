<?php

use App\Classes\Schema;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Determine whether the migration
     * should execute when we're accessing
     * a multistore instance.
     */
    public function runOnMultiStore()
    {
        return false;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission = Permission::namespace( 'nexopos.manage-payments-types' );

        if ( ! $permission instanceof Permission ) {
            $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.manage-payments-types' ] );
            $permission->namespace = 'nexopos.manage-payments-types';
            $permission->name = __( 'Manage Order Payment Types' );
            $permission->description = __( 'Allow to create, update and delete payments type.' );
            $permission->save();
        }

        Role::namespace( 'admin' )->addPermissions( $permission );
        Role::namespace( 'nexopos.store.administrator' )->addPermissions( $permission );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_permissions' ) ) {
            $permission = Permission::namespace( 'nexopos.manage-payments-types' );

            if ( $permission instanceof Permission ) {
                RolePermission::where( 'permission_id', $permission->id )->delete();
                $permission->delete();
            }
        }
    }
};
