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

    private $permission = 'nexopos.customers.manage-account-history';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission = Permission::namespace( $this->permission );

        if ( ! $permission instanceof Permission ) {
            $permission = Permission::firstOrNew( [ 'namespace' => $this->permission ] );
            $permission->namespace = $this->permission;
            $permission->name = __( 'Manage Customer Account History' );
            $permission->description = __( 'Can add, deduct amount from each customers account.' );
            $permission->save();
        }

        Role::namespace( 'admin' )->addPermissions( $this->permission );
        Role::namespace( 'nexopos.store.administrator' )->addPermissions( $this->permission );
        Role::namespace( 'nexopos.store.cashier' )->addPermissions( $this->permission );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_permissions' ) ) {
            $permission = Permission::namespace( $this->permission );

            if ( $permission instanceof Permission ) {
                RolePermission::where( 'permission_id', $permission->id )->delete();
                Permission::namespace( $this->permission )->delete();
            }
        }
    }
};
