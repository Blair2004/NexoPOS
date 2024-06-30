<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.create.products' ] );
    $product->name = __( 'Create Products' );
    $product->namespace = 'nexopos.create.products';
    $product->description = __( 'Let the user create products' );
    $product->save();

    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.delete.products' ] );
    $product->name = __( 'Delete Products' );
    $product->namespace = 'nexopos.delete.products';
    $product->description = __( 'Let the user delete products' );
    $product->save();

    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.update.products' ] );
    $product->name = __( 'Update Products' );
    $product->namespace = 'nexopos.update.products';
    $product->description = __( 'Let the user update products' );
    $product->save();

    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.read.products' ] );
    $product->name = __( 'Read Products' );
    $product->namespace = 'nexopos.read.products';
    $product->description = __( 'Let the user read products' );
    $product->save();

    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.convert.products-units' ] );
    $product->name = __( 'Convert Products Units' );
    $product->namespace = 'nexopos.convert.products-units';
    $product->description = __( 'Let the user convert products' );
    $product->save();

    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.read.products-history' ] );
    $product->name = __( 'Read Product History' );
    $product->namespace = 'nexopos.read.products-history';
    $product->description = __( 'Let the user read products history' );
    $product->save();

    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.make.products-adjustments' ] );
    $product->name = __( 'Adjust Product Stock' );
    $product->namespace = 'nexopos.make.products-adjustments';
    $product->description = __( 'Let the user adjust product stock.' );
    $product->save();

    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.create.products-units' ] );
    $product->name = __( 'Create Product Units/Unit Group' );
    $product->namespace = 'nexopos.create.products-units';
    $product->description = __( 'Let the user create products units.' );
    $product->save();

    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.read.products-units' ] );
    $product->name = __( 'Read Product Units/Unit Group' );
    $product->namespace = 'nexopos.read.products-units';
    $product->description = __( 'Let the user read products units.' );
    $product->save();

    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.update.products-units' ] );
    $product->name = __( 'Update Product Units/Unit Group' );
    $product->namespace = 'nexopos.update.products-units';
    $product->description = __( 'Let the user update products units.' );
    $product->save();

    $product = Permission::firstOrNew( [ 'namespace' => 'nexopos.delete.products-units' ] );
    $product->name = __( 'Delete Product Units/Unit Group' );
    $product->namespace = 'nexopos.delete.products-units';
    $product->description = __( 'Let the user delete products units.' );
    $product->save();
}
