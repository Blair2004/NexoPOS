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
        include dirname( __FILE__ ) . '/../../permissions/pos.php';

        Role::namespace( 'admin' )
            ->addPermissions( Permission::includes( '.pos' )
            ->get()->map( fn( $permission ) => $permission->namespace ) );

        Role::namespace( 'nexopos.store.administrator' )
            ->addPermissions( Permission::includes( '.pos' )
            ->get()->map( fn( $permission ) => $permission->namespace ) );

        Role::namespace( 'nexopos.store.cashier' )
            ->addPermissions( Permission::includes( '.pos' )
            ->get()->map( fn( $permission ) => $permission->namespace ) );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
