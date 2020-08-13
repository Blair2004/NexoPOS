<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $stores                 =   new Permission;
    $stores->name           =   __( 'Create Stores' );
    $stores->namespace      =   'nexopos.create.stores';
    $stores->description    =   __( 'Let the user create stores' );
    $stores->save();

    $stores                 =   new Permission;
    $stores->name           =   __( 'Delete Stores' );
    $stores->namespace      =   'nexopos.delete.stores';
    $stores->description    =   __( 'Let the user delete stores' );
    $stores->save();

    $stores                 =   new Permission;
    $stores->name           =   __( 'Update Stores' );
    $stores->namespace      =   'nexopos.update.stores';
    $stores->description    =   __( 'Let the user update stores' );
    $stores->save();

    $stores                 =   new Permission;
    $stores->name           =   __( 'Read Stores' );
    $stores->namespace      =   'nexopos.read.stores';
    $stores->description    =   __( 'Let the user read stores' );
    $stores->save();

    $stores                 =   new Permission;
    $stores->name           =   __( 'Access Stores' );
    $stores->namespace      =   'nexopos.access.stores';
    $stores->description    =   __( 'Let the user access to stores' );
    $stores->save();
}