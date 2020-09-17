<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $procurements                 =   new Permission;
    $procurements->name           =   __( 'Create Procurements' );
    $procurements->namespace      =   'nexopos.create.procurements';
    $procurements->description    =   __( 'Let the user create procurements' );
    $procurements->save();

    $procurements                 =   new Permission;
    $procurements->name           =   __( 'Delete Procurements' );
    $procurements->namespace      =   'nexopos.delete.procurements';
    $procurements->description    =   __( 'Let the user delete procurements' );
    $procurements->save();

    $procurements                 =   new Permission;
    $procurements->name           =   __( 'Update Procurements' );
    $procurements->namespace      =   'nexopos.update.procurements';
    $procurements->description    =   __( 'Let the user update procurements' );
    $procurements->save();

    $procurements                 =   new Permission;
    $procurements->name           =   __( 'Read Procurements' );
    $procurements->namespace      =   'nexopos.read.procurements';
    $procurements->description    =   __( 'Let the user read procurements' );
    $procurements->save();
}