<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ( ! Permission::namespace( 'nexopos.pos.delete-order-product' ) instanceof Permission ) {
            $pos = Permission::firstOrNew( [ 'namespace' => 'nexopos.pos.delete-order-product' ] );
            $pos->name = __( 'POS: Delete Order Product' );
            $pos->namespace = 'nexopos.pos.delete-order-product';
            $pos->description = __( 'Let the user delete order products on POS.' );
            $pos->save();

            /**
             * @var Role
             */
            $admin = Role::firstOrNew( [ 'namespace' => Role::ADMIN ] );

            /**
             * @var Role
             */
            $storeAdmin = Role::firstOrNew( [ 'namespace' => Role::STOREADMIN ] );

            $admin->addPermissions( $pos );
            $storeAdmin->addPermissions( $pos );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::namespace( 'nexopos.pos.delete-order-product' );
        $permission->removeFromRoles();
        $permission->delete();
    }
};
