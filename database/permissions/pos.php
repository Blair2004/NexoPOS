<?php

use App\Models\Permission;
use App\Models\Role;

$posReadPermissions = [
    'nexopos.pos.access' => [ __( 'POS: Access' ), __( 'Access the point of sale workspace.' ) ],
    'nexopos.pos.read-products' => [ __( 'POS: Read Products' ), __( 'Browse the sale catalog through POS workflows.' ) ],
    'nexopos.pos.read-own-orders' => [ __( 'POS: Read Own Orders' ), __( 'Read orders created by the current user through POS workflows.' ) ],
    'nexopos.pos.read-all-orders' => [ __( 'POS: Read All Orders' ), __( 'Read every order through POS workflows.' ) ],
];

foreach ( $posReadPermissions as $namespace => [$name, $description] ) {
    if ( Permission::where( 'name', $name )->where( 'namespace', '!=', $namespace )->exists() ) {
        throw new RuntimeException( sprintf( 'The permission name "%s" is already assigned to another namespace.', $name ) );
    }

    $permission = Permission::firstOrNew( [ 'namespace' => $namespace ] );
    $permission->name = $name;
    $permission->namespace = $namespace;
    $permission->description = $description;
    $permission->save();
}

if ( ! Permission::namespace( 'nexopos.pos.edit-purchase-price' ) instanceof Permission ) {
    $pos = Permission::firstOrNew( [ 'namespace' => 'nexopos.pos.edit-purchase-price' ] );
    $pos->name = __( 'Edit Purchase Price' );
    $pos->namespace = 'nexopos.pos.edit-purchase-price';
    $pos->description = __( 'Let the user edit the purchase price of products.' );
    $pos->save();
}

if ( ! Permission::namespace( 'nexopos.pos.edit-settings' ) instanceof Permission ) {
    $pos = Permission::firstOrNew( [ 'namespace' => 'nexopos.pos.edit-settings' ] );
    $pos->name = __( 'Edit Order Settings' );
    $pos->namespace = 'nexopos.pos.edit-settings';
    $pos->description = __( 'Let the user edit the order settings.' );
    $pos->save();
}

if ( ! Permission::namespace( 'nexopos.pos.products-discount' ) instanceof Permission ) {
    $pos = Permission::firstOrNew( [ 'namespace' => 'nexopos.pos.products-discount' ] );
    $pos->name = __( 'Edit Product Discounts' );
    $pos->namespace = 'nexopos.pos.products-discount';
    $pos->description = __( 'Let the user add discount on products.' );
    $pos->save();
}

if ( ! Permission::namespace( 'nexopos.pos.cart-discount' ) instanceof Permission ) {
    $pos = Permission::firstOrNew( [ 'namespace' => 'nexopos.pos.cart-discount' ] );
    $pos->name = __( 'Edit Cart Discounts' );
    $pos->namespace = 'nexopos.pos.cart-discount';
    $pos->description = __( 'Let the user add discount on cart.' );
    $pos->save();
}

if ( ! Permission::namespace( 'nexopos.pos.delete-order-product' ) instanceof Permission ) {
    $pos = Permission::firstOrNew( [ 'namespace' => 'nexopos.pos.delete-order-product' ] );
    $pos->name = __( 'POS: Delete Order Products' );
    $pos->namespace = 'nexopos.pos.delete-order-product';
    $pos->description = __( 'Let the user delete order products on POS.' );
    $pos->save();
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
    $admin->addPermissions( array_merge( array_keys( $posReadPermissions ), array_keys( $posCartPermissions ) ) );
}
