<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetCashierPermissions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $role   =   Role::namespace( Role::STORECASHIER );
        
        Permission::where( 'namespace', 'like', '%nexopos.pos%' )
            ->get()
            ->each( function( $permission ) use ( $role ) {
                $role->addPermissions( $permission->namespace );
            });

        /**
         * Allow the cashier to create customers
         */
        $role->addPermissions( 'nexopos.create.customers' );
        $role->addPermissions( 'nexopos.update.customers' );
        $role->addPermissions( 'nexopos.read.customers' );
        $role->addPermissions( 'nexopos.customers.manage-account' );
        $role->addPermissions( 'nexopos.customers.manage-account-history' );

        /**
         * We'll add permissions to manage orders
         */
        $role->addPermissions( 'nexopos.create.orders' );
        $role->addPermissions( 'nexopos.update.orders' );
        $role->addPermissions( 'nexopos.read.orders' );
        $role->addPermissions( 'nexopos.make-payment.orders' );

        /**
         * Add permission for managing cash registers
         */
        $role->addPermissions( 'nexopos.read.registers' );
        $role->addPermissions( 'nexopos.read.registers-history' );
        $role->addPermissions( 'nexopos.use.registers' );
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
