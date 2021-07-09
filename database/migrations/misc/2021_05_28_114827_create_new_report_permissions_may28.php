<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNewReportPermissionsMay28 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission     =   Permission::namespace( 'nexopos.reports.payment-types' );

        if ( ! $permission instanceof Permission ) {
            $permission                 =   new Permission;
            $permission->name           =   __( 'Read Sales by Payment Types' );
            $permission->namespace      =   'nexopos.reports.payment-types';
            $permission->description    =   __( 'Let the user read the report that shows sales by payment types.' );
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
