<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission = Permission::namespace( 'nexopos.reports.customers-statement' );

        if ( ! $permission instanceof Permission ) {
            $permission = Permission::firstOrNew([ 'namespace' => 'nexopos.reports.customers-statement' ]);
            $permission->name = __( 'See Customers Statement' );
            $permission->namespace = 'nexopos.reports.customers-statement';
            $permission->description = __( 'Allow the user to see the customers statement.' );
            $permission->save();
        }

        Role::namespace( Role::ADMIN )->addPermissions( $permission );
        Role::namespace( Role::STOREADMIN )->addPermissions( $permission );
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
