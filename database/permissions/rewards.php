<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $reward                 =   new Permission;
    $reward->name           =   __( 'Create Rewards' );
    $reward->namespace      =   'nexopos.create.reward';
    $reward->description    =   __( 'Let the user create Rewards' );
    $reward->save();

    $reward                 =   new Permission;
    $reward->name           =   __( 'Delete Rewards' );
    $reward->namespace      =   'nexopos.delete.reward';
    $reward->description    =   __( 'Let the user delete Rewards' );
    $reward->save();

    $reward                 =   new Permission;
    $reward->name           =   __( 'Update Rewards' );
    $reward->namespace      =   'nexopos.update.reward';
    $reward->description    =   __( 'Let the user update Rewards' );
    $reward->save();

    $reward                 =   new Permission;
    $reward->name           =   __( 'Read Rewards' );
    $reward->namespace      =   'nexopos.read.reward';
    $reward->description    =   __( 'Let the user read Rewards' );
    $reward->save();
}