<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $providers                 =   new Permission;
    $providers->name           =   __( 'Create Providers' );
    $providers->namespace      =   'nexopos.create.providers';
    $providers->description    =   __( 'Let the user create providers' );
    $providers->save();

    $providers                 =   new Permission;
    $providers->name           =   __( 'Delete Providers' );
    $providers->namespace      =   'nexopos.delete.providers';
    $providers->description    =   __( 'Let the user delete providers' );
    $providers->save();

    $providers                 =   new Permission;
    $providers->name           =   __( 'Update Providers' );
    $providers->namespace      =   'nexopos.update.providers';
    $providers->description    =   __( 'Let the user update providers' );
    $providers->save();

    $providers                 =   new Permission;
    $providers->name           =   __( 'Read Providers' );
    $providers->namespace      =   'nexopos.read.providers';
    $providers->description    =   __( 'Let the user read providers' );
    $providers->save();
}