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
        $storeAdmin->locked         =   true;
        $storeAdmin->description    =   __( 'Has a control over an entire store of NexoPOS.' );
        $storeAdmin->save();
        $storeAdmin->addPermissions([ 'manage.options', 'manage.profile' ]);
        $storeAdmin->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.categories' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.customers' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.products' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.expenses' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.orders' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.coupons' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.expenses-categories' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.procurements' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.providers' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.report.' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.rewards' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.registers' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.stores' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.taxes' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.trucks' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );

        
        /**
         * store administrator role
         */
        $storeCashier               =   new Role;
        $storeCashier->name         =   __( 'Store Cashier' );
        $storeCashier->namespace    =   'nexopos.store.cashier';
        $storeCashier->locked       =   true;
        $storeCashier->description  =   __( 'Has a control over the sale process.' );
        $storeCashier->save();
        $storeCashier->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );

        /**
         * store administrator role
         */
        $driver                     =   new Role;
        $driver->name               =   __( 'Vehicule Driver' );
        $driver->namespace          =   'nexopos.store.driver';
        $driver->locked             =   true;
        $driver->description        =   __( 'Does the orders delivery.' );
        $driver->save();
        $driver->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );    }

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

        $role   =   Role::where( 'namespace', 'nexopos.store.drivers' )->first();
        if ( $role instanceof Role ) {
            $role->delete();
        }
    }
}
