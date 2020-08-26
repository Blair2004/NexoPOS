<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $category                 =   new Permission;
    $category->name           =   __( 'Create Categories' );
    $category->namespace      =   'nexopos.create.categories';
    $category->description    =   __( 'Let the user create products categories.' );
    $category->save();
    
    $category                 =   new Permission;
    $category->name           =   __( 'Delete Categories' );
    $category->namespace      =   'nexopos.delete.categories';
    $category->description    =   __( 'Let the user delete products categories.' );
    $category->save();
    
    $category                 =   new Permission;
    $category->name           =   __( 'Update Categories' );
    $category->namespace      =   'nexopos.update.categories';
    $category->description    =   __( 'Let the user update products categories.' );
    $category->save();
    
    $category                 =   new Permission;
    $category->name           =   __( 'Read Categories' );
    $category->namespace      =   'nexopos.read.categories';
    $category->description    =   __( 'Let the user read products categories.' );
    $category->save();
}
