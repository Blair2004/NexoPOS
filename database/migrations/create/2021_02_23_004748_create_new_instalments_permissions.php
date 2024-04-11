<?php

use App\Classes\Schema;
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
        $createInstalment = Permission::firstOrNew( [ 'namespace' => 'nexopos.create.orders-instalments' ] );
        $createInstalment->namespace = 'nexopos.create.orders-instalments';
        $createInstalment->name = __( 'Create Instalment' );
        $createInstalment->description = __( 'Allow the user to create instalments.' );
        $createInstalment->save();

        $updateInstalment = Permission::firstOrNew( [ 'namespace' => 'nexopos.update.orders-instalments' ] );
        $updateInstalment->namespace = 'nexopos.update.orders-instalments';
        $updateInstalment->name = __( 'Update Instalment' );
        $updateInstalment->description = __( 'Allow the user to update instalments.' );
        $updateInstalment->save();

        $readInstalment = Permission::firstOrNew( [ 'namespace' => 'nexopos.read.orders-instalments' ] );
        $readInstalment->namespace = 'nexopos.read.orders-instalments';
        $readInstalment->name = __( 'Read Instalment' );
        $readInstalment->description = __( 'Allow the user to read instalments.' );
        $readInstalment->save();

        $deleteInstalments = Permission::firstOrNew( [ 'namespace' => 'nexopos.delete.orders-instalments' ] );
        $deleteInstalments->namespace = 'nexopos.delete.orders-instalments';
        $deleteInstalments->name = __( 'Delete Instalment' );
        $deleteInstalments->description = __( 'Allow the user to delete instalments.' );
        $deleteInstalments->save();

        Role::namespace( 'admin' )->addPermissions( [
            $createInstalment,
            $updateInstalment,
            $readInstalment,
            $deleteInstalments,
        ] );

        Role::namespace( 'nexopos.store.administrator' )->addPermissions( [
            $createInstalment,
            $updateInstalment,
            $readInstalment,
            $deleteInstalments,
        ] );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if ( Schema::hasTable( 'nexopos_permissions' ) && Schema::hasTable( 'nexopos_roles' ) ) {
            collect( [
                'nexopos.create.orders-instalments',
                'nexopos.update.orders-instalments',
                'nexopos.read.orders-instalments',
                'nexopos.delete.orders-instalments',
            ] )->each( function ( $identifier ) {
                $permission = Permission::where( 'namespace', $identifier
                )->first();

                $permission->removeFromRoles();
                $permission->delete();
            } );
        }
    }
};
