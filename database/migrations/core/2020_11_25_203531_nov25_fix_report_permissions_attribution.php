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
        Permission::where( 'namespace', 'like', '%.report.%' )
            ->get()
            ->each( function ( $permission ) {
                $permission->namespace = str_replace( '.report.', '.reports.', $permission->namespace );
                $permission->save();
            } );

        $permissions = Permission::where( 'namespace', 'like', '%.reports.%' )
            ->get();

        if ( $permissions->count() > 0 ) {
            Role::namespace( 'admin' )->addPermissions( $permissions );
            Role::namespace( 'nexopos.store.administrator' )->addPermissions( $permissions );
        }
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
