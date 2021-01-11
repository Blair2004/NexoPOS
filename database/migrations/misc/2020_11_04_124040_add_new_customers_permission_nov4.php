<?php

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use App\Classes\Schema;

class AddNewCustomersPermissionNov4 extends Migration
{
    private $permission     =   'nexopos.customers.manage-account-history';
    public $multistore      =   false;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission     =   Permission::namespace( $this->permission );

        if ( ! $permission instanceof Permission ) {
            $permission                 =   new Permission;
            $permission->namespace      =   $this->permission;
            $permission->name           =   __( 'Manage Customer Account' );
            $permission->description    =   __( 'Can add, deduct amount from each customers account.' );
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
            $permission     =   Permission::namespace( $this->permission );
    
            if ( $permission instanceof Permission ) {
                RolePermission::where( 'permission_id', $permission->id )->delete();
                Permission::namespace( $this->permission )->delete();
            }
        }
    }
}
