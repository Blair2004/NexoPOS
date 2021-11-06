<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $permission                 =   new Permission;
    $permission->name           =   __( 'See Sale Report' );
    $permission->namespace      =   'nexopos.reports.sales';
    $permission->description    =   __( 'Let you see the sales report' );
    $permission->save();

    $permission                 =   new Permission;
    $permission->name           =   __( 'See Products Report' );
    $permission->namespace      =   'nexopos.reports.products-report';
    $permission->description    =   __( 'Let you see the Products report' );
    $permission->save();

    $permission                 =   new Permission;
    $permission->name           =   __( 'See Best Report' );
    $permission->namespace      =   'nexopos.reports.best_sales';
    $permission->description    =   __( 'Let you see the best_sales report' );
    $permission->save();

    $permission                 =   new Permission;
    $permission->name           =   __( 'See Cash Flow Report' );
    $permission->namespace      =   'nexopos.reports.cash_flow';
    $permission->description    =   __( 'Let you see the cash flow report' );
    $permission->save();

    $permission                 =   new Permission;
    $permission->name           =   __( 'See Yearly Sales' );
    $permission->namespace      =   'nexopos.reports.yearly';
    $permission->description    =   __( 'Let you see the yearly report' );
    $permission->save();

    $permission                 =   new Permission;
    $permission->name           =   __( 'See Customers' );
    $permission->namespace      =   'nexopos.reports.customers';
    $permission->description    =   __( 'Let you see the Yearly report' );
    $permission->save();

    $permission                 =   new Permission;
    $permission->name           =   __( 'See Inventory Tracking' );
    $permission->namespace      =   'nexopos.reports.inventory';
    $permission->description    =   __( 'Let you see the Yearly report' );
    $permission->save();

    $permission                 =   new Permission;
    $permission->name           =   __( 'Read Sales by Payment Types' );
    $permission->namespace      =   'nexopos.reports.payment-types';
    $permission->description    =   __( 'Let the user read the report that shows sales by payment types.' );
    $permission->save();

    $permission                 =   new Permission;
    $permission->name           =   __( 'Read Low Stock Report' );
    $permission->namespace      =   'nexopos.reports.low-stock';
    $permission->description    =   __( 'Let the user read the report that shows low stock.' );
    $permission->save();
}
