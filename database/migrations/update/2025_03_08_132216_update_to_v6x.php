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
        ] );

        Schema::table( 'nexopos_notifications', function ( Blueprint $table ) {
            if ( ! Schema::hasColumn( 'nexopos_notifications', 'actions' ) ) {
                $table->json( 'actions' )->nullable();
            }
        } );

        /**
         * Create POS action permissions for v6.x
         */
        if ( ! defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
            define( 'NEXO_CREATE_PERMISSIONS', true );
        }

        // Create POS cart action permissions
        $posCartPermissions = [
            'nexopos.cart.product-discount' => __( 'Cart: Change Product Discount' ),
            'nexopos.cart.product-price' => __( 'Cart: Edit Product Price' ),
            'nexopos.cart.product-wholesale-price' => __( 'Cart: Use Wholesale Price' ),
            'nexopos.cart.product-delete' => __( 'Cart: Product Delete' ),
            'nexopos.cart.settings' => __( 'Cart: Change Settings' ),
            'nexopos.cart.taxes' => __( 'Cart: Set Taxes' ),
            'nexopos.cart.comments' => __( 'Cart: Add Comments' ),
            'nexopos.cart.order-type' => __( 'Cart: Change Order Type' ),
            'nexopos.cart.coupons' => __( 'Cart: Apply Coupons' ),
            'nexopos.cart.products' => __( 'Cart: Create Quick Product' ),
            'nexopos.cart.void' => __( 'Cart: Void Order' ),
            'nexopos.cart.discount' => __( 'Cart: Apply Discount' ),
            'nexopos.cart.hold' => __( 'Cart: Hold Order' ),
        ];

        foreach ( $posCartPermissions as $namespace => $name ) {
            $permission = Permission::firstOrNew( [ 'namespace' => $namespace ] );
            $permission->name = $name;
            $permission->namespace = $namespace;
            $permission->description = sprintf( __( 'Allow access to %s feature in POS cart.' ), strtolower( $name ) );
            $permission->save();
        }

        /**
         * Assign new permissions to admin role
         */
        $admin = Role::namespace( 'admin' );
        if ( $admin instanceof Role ) {
            $admin->addPermissions( array_keys( $posCartPermissions ) );
        }

        /**
         * We'll make the column "order_id" optional on the table
         * `nexopos_customers_account_history`.
         */
        Schema::table( 'nexopos_customers_account_history', function ( Blueprint $table ) {
            $table->integer( 'order_id' )->nullable()->change();
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
