<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $coupons                 =   new Permission;
    $coupons->name           =   __( 'Create Coupons' );
    $coupons->namespace      =   'nexopos.create.coupons';
    $coupons->description    =   __( 'Let the user create coupons' );
    $coupons->save();

    $coupons                 =   new Permission;
    $coupons->name           =   __( 'Delete Coupons' );
    $coupons->namespace      =   'nexopos.delete.coupons';
    $coupons->description    =   __( 'Let the user delete coupons' );
    $coupons->save();

    $coupons                 =   new Permission;
    $coupons->name           =   __( 'Update Coupons' );
    $coupons->namespace      =   'nexopos.update.coupons';
    $coupons->description    =   __( 'Let the user update coupons' );
    $coupons->save();

    $coupons                 =   new Permission;
    $coupons->name           =   __( 'Read Coupons' );
    $coupons->namespace      =   'nexopos.read.coupons';
    $coupons->description    =   __( 'Let the user read coupons' );
    $coupons->save();
}