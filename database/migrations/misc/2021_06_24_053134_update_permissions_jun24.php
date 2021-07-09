<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePermissionsJun24 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission     =   Permission::namespace( 'nexopos.reports.products-report' );

        if ( ! $permission instanceof Permission ) {
            $permission                 =   new Permission;
            $permission->name           =   __( 'See Products Report' );
            $permission->namespace      =   'nexopos.reports.products-report';
            $permission->description    =   __( 'Let you see the Products report' );
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
        //
    }
}
