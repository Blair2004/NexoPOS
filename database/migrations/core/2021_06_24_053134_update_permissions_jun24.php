<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Determine whether the migration
     * should execute when we're accessing
     * a multistore instance.
     */
    public function runOnMultiStore()
    {
        return false;
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission = Permission::namespace( 'nexopos.reports.products-report' );

        if ( ! $permission instanceof Permission ) {
            $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.products-report' ] );
            $permission->name = __( 'See Products Report' );
            $permission->namespace = 'nexopos.reports.products-report';
            $permission->description = __( 'Let you see the Products report' );
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
};
