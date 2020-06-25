<?php
use Tendoo\Core\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $product                 =   new Permission;
    $product->name           =   __( 'Create Products' );
    $product->namespace      =   'nexopos.create.products';
    $product->description    =   __( 'Let the user create products' );
    $product->save();

    $product                 =   new Permission;
    $product->name           =   __( 'Delete Products' );
    $product->namespace      =   'nexopos.delete.products';
    $product->description    =   __( 'Let the user delete products' );
    $product->save();

    $product                 =   new Permission;
    $product->name           =   __( 'Update Products' );
    $product->namespace      =   'nexopos.update.products';
    $product->description    =   __( 'Let the user update products' );
    $product->save();

    $product                 =   new Permission;
    $product->name           =   __( 'Read Products' );
    $product->namespace      =   'nexopos.read.products';
    $product->description    =   __( 'Let the user read products' );
    $product->save();

    $product                 =   new Permission;
    $product->name           =   __( 'Read Product History' );
    $product->namespace      =   'nexopos.read.products-history';
    $product->description    =   __( 'Let the user read products history' );
    $product->save();
}