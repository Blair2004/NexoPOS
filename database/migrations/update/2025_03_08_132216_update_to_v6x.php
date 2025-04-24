<?php

use App\Classes\Schema;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call( 'ns:doctor', [
            '--purge-orphan-migrations' => true,
        ]);

        Schema::table( 'nexopos_notifications', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_notifications', 'actions' ) ) {
                $table->json( 'actions' )->nullable();
            }
        } );

        define( 'NEXO_CREATE_PERMISSIONS', true );

        include_once dirname( __FILE__ ) . '/../../permissions/drivers-permissions.php';
        include_once dirname( __FILE__ ) . '/../../permissions/driver-role.php';

        /**
         * we'll manually add the permission "drivers" to the administrator
         */
        $admin = Role::where( 'namespace', 'admin' )->first();
        $admin->addPermissions( Permission::includes( '.drivers' )->get()->map( fn( $permission ) => $permission->namespace ) );

        /**
         * We'll update the orders table to add the "driver_id"
         */
        Schema::table( 'nexopos_orders', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_orders', 'driver_id' ) ) {
                $table->integer( 'driver_id' )->nullable();
            }
        } );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table( 'nexopos_notifications', function ( Blueprint $table ) {
            $table->dropColumn( 'actions' );
        } );
    }
};
