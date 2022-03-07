<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAddNewPermissionsAug5 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        include( dirname( __FILE__ ) . '/../../permissions/pos.php' );

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
}
