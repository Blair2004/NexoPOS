<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $orders                 =   new Permission;
    $orders->name           =   __( 'Create Orders' );
    $orders->namespace      =   'nexopos.create.orders';
    $orders->description    =   __( 'Let the user create orders' );
    $orders->save();

    $orders                 =   new Permission;
    $orders->name           =   __( 'Delete Orders' );
    $orders->namespace      =   'nexopos.delete.orders';
    $orders->description    =   __( 'Let the user delete orders' );
    $orders->save();

    $orders                 =   new Permission;
    $orders->name           =   __( 'Update Orders' );
    $orders->namespace      =   'nexopos.update.orders';
    $orders->description    =   __( 'Let the user update orders' );
    $orders->save();

    $orders                 =   new Permission;
    $orders->name           =   __( 'Read Orders' );
    $orders->namespace      =   'nexopos.read.orders';
    $orders->description    =   __( 'Let the user read orders' );
    $orders->save();

    $orders                 =   new Permission;
    $orders->name           =   __( 'Void Order' );
    $orders->namespace      =   'nexopos.void.orders';
    $orders->description    =   __( 'Let the user void orders' );
    $orders->save();

    $orders                 =   new Permission;
    $orders->name           =   __( 'Refund Order' );
    $orders->namespace      =   'nexopos.refund.orders';
    $orders->description    =   __( 'Let the user refund orders' );
    $orders->save();

    $orders                 =   new Permission;
    $orders->name           =   __( 'Make Payment To orders' );
    $orders->namespace      =   'nexopos.make-payment.orders';
    $orders->description    =   __( 'Allow the user to make payments to orders.' );
    $orders->save();
}