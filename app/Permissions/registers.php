<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $registers                 =   new Permission;
    $registers->name           =   __( 'Create Registers' );
    $registers->namespace      =   'nexopos.create.registers';
    $registers->description    =   __( 'Let the user create registers' );
    $registers->save();

    $registers                 =   new Permission;
    $registers->name           =   __( 'Delete Registers' );
    $registers->namespace      =   'nexopos.delete.registers';
    $registers->description    =   __( 'Let the user delete registers' );
    $registers->save();

    $registers                 =   new Permission;
    $registers->name           =   __( 'Update Registers' );
    $registers->namespace      =   'nexopos.update.registers';
    $registers->description    =   __( 'Let the user update registers' );
    $registers->save();

    $registers                 =   new Permission;
    $registers->name           =   __( 'Read Registers' );
    $registers->namespace      =   'nexopos.read.registers';
    $registers->description    =   __( 'Let the user read registers' );
    $registers->save();

    $registers                 =   new Permission;
    $registers->name           =   __( 'Read Registers History' );
    $registers->namespace      =   'nexopos.read.registers-history';
    $registers->description    =   __( 'Let the user read registers history' );
    $registers->save();

    $registers                 =   new Permission;
    $registers->name           =   __( 'Read Use Registers' );
    $registers->namespace      =   'nexopos.use.registers';
    $registers->description    =   __( 'Let the user use registers' );
    $registers->save();
}