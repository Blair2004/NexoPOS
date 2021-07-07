<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class Nov12CreateOrderPermission extends Migration
{
    const permissionName    =   'nexopos.make-payment.orders';

    public $multistore      =   false;
    
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission     =   Permission::namespace( self::permissionName );

        if ( ! $permission instanceof Permission ) {
            $permission                 =   new Permission;
            $permission->namespace      =   self::permissionName;
            $permission->name           =   __( 'Make Payment To Orders' );
            $permission->description    =   __( 'Allow the user to perform additional payment for a specific incomplete order.' );
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
            $permission     =   Permission::namespace( self::permissionName );
            $permission->removeFromRoles();
        }
    }
}
