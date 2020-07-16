<?php
/**
 * Table Migration
 * @package  5.0
**/
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use App\Services\Options;

class CreateRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return  void
     */
    public function up()
    {
        $this->options      =   app()->make( Options::class );
        
        /**
         * store administrator role
         */
        $storeAdmin                 =   new Role;
        $storeAdmin->name           =   __( 'Store Administrator' );
        $storeAdmin->namespace      =   'nexopos.store.administrator';
        $storeAdmin->description    =   __( 'Has a control over an entire store of NexoPOS' );
        $storeAdmin->save();
        
        /**
         * store administrator role
         */
        $storeCashier               =   new Role;
        $storeCashier->name         =   __( 'Store Cashier' );
        $storeCashier->namespace    =   'nexopos.store.cashier';
        $storeCashier->description  =   __( 'Has a control over the sale process' );
        $storeCashier->save();

        /**
         * assigning permissions to roles
         */
        $storeAdmin         =   Role::namespace( 'nexopos.store.administrator' );
        $storeAdmin->grantPermissions( Permission::includes( '.categories' )->get() );
        $storeAdmin->grantPermissions( Permission::includes( '.products' )->get() );
        $storeAdmin->grantPermissions( Permission::includes( '.expenses' )->get() );
        $storeAdmin->grantPermissions( Permission::includes( '.orders' )->get() );
        $storeAdmin->grantPermissions( Permission::includes( '.coupons' )->get() );
        $storeAdmin->grantPermissions( Permission::includes( '.expenses-categories' )->get() );
        $storeAdmin->grantPermissions( Permission::includes( '.procurements' )->get() );
        $storeAdmin->grantPermissions( Permission::includes( '.registers' )->get() );
        $storeAdmin->grantPermissions( Permission::includes( '.stores' )->get() );
        $storeAdmin->grantPermissions( Permission::includes( '.taxes' )->get() );
    }

    /**
     * Reverse the migrations.
     *
     * @return  void
     */
    public function down()
    {
        $role   =   Role::where( 'namespace', 'nexopos.store.administrator' )->first();
        if ( $role instanceof Role ) {
            $role->delete();
        }

        $role   =   Role::where( 'namespace', 'nexopos.store.cashier' )->first();
        if ( $role instanceof Role ) {
            $role->delete();
        }
    }
}
