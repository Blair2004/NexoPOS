<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( ! Schema::hasTable( 'nexopos_permissions_access' ) ) {
            Schema::create( 'nexopos_permissions_access', function ( Blueprint $table ) {
                $table->id();
                $table->integer( 'requester_id' )->unsigned()->index();
                $table->integer( 'granter_id' )->unsigned()->index();
                $table->string( 'permission' )->index();
                $table->string( 'url' )->nullable();
                $table->enum( 'status', [ 'granted', 'denied', 'pending', 'expired', 'used' ] )->default( 'pending' );
                $table->datetime( 'expired_at' )->nullable();
                $table->timestamps();
            } );
        }

        /**
         * Create permissions for permission access management
         */
        if ( ! defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
            define( 'NEXO_CREATE_PERMISSIONS', true );
        }

        // Create CRUD permissions for permission access
        $permissions = [];
        foreach ( [ 'create', 'read', 'update', 'delete' ] as $crud ) {
            $permission = Permission::firstOrNew( [ 'namespace' => $crud . '.permissions-access' ] );
            $permission->name = ucwords( $crud ) . ' ' . __( 'Permission Access' );
            $permission->namespace = $crud . '.permissions-access';
            $permission->description = sprintf( __( 'Can %s permission access records' ), $crud );
            $permission->save();

            $permissions[] = $permission->namespace;
        }

        // Assign permissions to admin role
        $admin = Role::firstOrNew( [ 'namespace' => Role::ADMIN ] );
        if ( $admin->exists ) {
            $admin->addPermissions( $permissions );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists( 'nexopos_permissions_access' );
    }
};
