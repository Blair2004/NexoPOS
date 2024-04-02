<?php

use App\Models\Permission;
use App\Models\Role;
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
        $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.create.products-labels' ] );
        $permission->name = __( 'Create Products Labels' );
        $permission->description = __( 'Allow the user to create products labels' );
        $permission->save();

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
        $permission = Permission::namespace( 'nexopos.create.products-labels' );
        $permission->removeFromRoles();
        $permission->delete();
    }
};
