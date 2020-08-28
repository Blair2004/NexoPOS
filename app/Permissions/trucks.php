<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $trucks                 =   new Permission;
    $trucks->name           =   __( 'Create Trucks' );
    $trucks->namespace      =   'nexopos.create.trucks';
    $trucks->description    =   __( 'Let the user create trucks' );
    $trucks->save();

    $trucks                 =   new Permission;
    $trucks->name           =   __( 'Delete Trucks' );
    $trucks->namespace      =   'nexopos.delete.trucks';
    $trucks->description    =   __( 'Let the user delete trucks' );
    $trucks->save();

    $trucks                 =   new Permission;
    $trucks->name           =   __( 'Update Trucks' );
    $trucks->namespace      =   'nexopos.update.trucks';
    $trucks->description    =   __( 'Let the user update trucks' );
    $trucks->save();

    $trucks                 =   new Permission;
    $trucks->name           =   __( 'Read Trucks' );
    $trucks->namespace      =   'nexopos.read.trucks';
    $trucks->description    =   __( 'Let the user read trucks' );
    $trucks->save();
}