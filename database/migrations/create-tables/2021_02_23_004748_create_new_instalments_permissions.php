<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewInstalmentsPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $createInstalment                 =   new Permission;
        $createInstalment->namespace      =   'nexopos.create.orders-instalments';
        $createInstalment->name           =   __( 'Create Instalment' );
        $createInstalment->description    =   __( 'Allow the user to create instalments.' );
        $createInstalment->save();

        $updateInstalment                 =   new Permission;
        $updateInstalment->namespace      =   'nexopos.update.orders-instalments';
        $updateInstalment->name           =   __( 'Update Instalment' );
        $updateInstalment->description    =   __( 'Allow the user to update instalments.' );
        $updateInstalment->save();

        $readInstalment                 =   new Permission;
        $readInstalment->namespace      =   'nexopos.read.orders-instalments';
        $readInstalment->name           =   __( 'Read Instalment' );
        $readInstalment->description    =   __( 'Allow the user to read instalments.' );
        $readInstalment->save();

        $deleteInstalments                 =   new Permission;
        $deleteInstalments->namespace      =   'nexopos.delete.orders-instalments';
        $deleteInstalments->name           =   __( 'Delete Instalment' );
        $deleteInstalments->description    =   __( 'Allow the user to delete instalments.' );
        $deleteInstalments->save();

        Role::namespace( 'admin' )->addPermissions([
            $createInstalment,
            $updateInstalment,
            $readInstalment,
            $deleteInstalments,
        ]);
        Role::namespace( 'nexopos.store.administrator' )->addPermissions([
            $createInstalment,
            $updateInstalment,
            $readInstalment,
            $deleteInstalments,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        collect([
            'nexopos.create.orders-instalments',
            'nexopos.update.orders-instalments',
            'nexopos.read.orders-instalments',
            'nexopos.delete.orders-instalments',
        ])->each( function( $identifier ) {
            $permission     =   Permission::where( 'namespace', $identifier 
                )->first();

            $permission->removeFromRoles();
            $permission->delete();
        });
    }
}
