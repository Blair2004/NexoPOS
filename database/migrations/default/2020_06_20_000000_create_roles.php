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

        // User Role
        $user               =   new Role;
        $user->name         =   __( 'User' );
        $user->namespace    =   'user';
        $user->locked       =   true;
        $user->dashid       =   'default';
        $user->description  =   __( 'Basic user role.' );
        $user->save();
        $user->addPermissions([ 
            'manage.profile' 
        ]); 

        // Master User
        $admin                      =   new Role;
        $admin->name                =   __( 'Administrator' );
        $admin->namespace           =   'admin';
        $admin->dashid              =   'store';
        $admin->locked              =   true;
        $admin->description         =   __( 'Master role which can perform all actions like create users, install/update/delete modules and much more.' );
        $admin->save(); 
        $admin->addPermissions([ 
            'create.users', 
            'read.users', 
            'update.users', 
            'delete.users', 
            'create.roles', 
            'read.roles', 
            'update.roles', 
            'delete.roles', 
            'update.core',
            'manage.profile', 
            'manage.options', 
            'manage.modules',
            'read.dashboard',
        ]);

        $admin->addPermissions( Permission::includes( '.expenses' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.cash-flow-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.expenses-categories' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.medias' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.categories' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.customers' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.customers-groups' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.coupons' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.orders' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.procurements' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.providers' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.products' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.products-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.products-adjustments' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.products-units' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.registers' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.registers-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.rewards' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.reports.' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.stores' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.taxes' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.trucks' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.units' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.manage-payments-types' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $admin->addPermissions( Permission::includes( '.pos' )->get()->map( fn( $permission ) => $permission->namespace ) );
        
        /**
         * store administrator role
         */
        $storeAdmin                 =   new Role;
        $storeAdmin->name           =   __( 'Store Administrator' );
        $storeAdmin->namespace      =   'nexopos.store.administrator';
        $storeAdmin->locked         =   true;
        $storeAdmin->description    =   __( 'Has a control over an entire store of NexoPOS.' );
        $storeAdmin->save();
        $storeAdmin->addPermissions([ 'read.dashboard' ]);
        $storeAdmin->addPermissions( Permission::includes( '.expenses' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.cash-flow-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.expenses-categories' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.categories' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.customers' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.customers-groups' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.coupons' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.orders' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.procurements' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.providers' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.products' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.products-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.products-adjustments' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.products-units' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.registers' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.registers-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.rewards' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.reports.' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.stores' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.taxes' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.trucks' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.units' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.manage-payments-types' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeAdmin->addPermissions( Permission::includes( '.pos' )->get()->map( fn( $permission ) => $permission->namespace ) );

        
        /**
         * store administrator role
         */
        $storeCashier               =   new Role;
        $storeCashier->name         =   __( 'Store Cashier' );
        $storeCashier->namespace    =   'nexopos.store.cashier';
        $storeCashier->locked       =   true;
        $storeCashier->description  =   __( 'Has a control over the sale process.' );
        $storeCashier->save();
        $storeCashier->addPermissions([ 'read.dashboard' ]);
        $storeCashier->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );
        $storeCashier->addPermissions( Permission::includes( '.pos' )->get()->map( fn( $permission ) => $permission->namespace ) );
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

        $role   =   Role::where( 'namespace', 'nexopos.store.drivers' )->first();
        if ( $role instanceof Role ) {
            $role->delete();
        }
    }
}
