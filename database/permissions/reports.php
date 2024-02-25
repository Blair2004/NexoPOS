<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.sales' ] );
    $permission->name = __( 'See Sale Report' );
    $permission->namespace = 'nexopos.reports.sales';
    $permission->description = __( 'Let you see the sales report' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.products-report' ] );
    $permission->name = __( 'See Products Report' );
    $permission->namespace = 'nexopos.reports.products-report';
    $permission->description = __( 'Let you see the Products report' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.best_sales' ] );
    $permission->name = __( 'See Best Report' );
    $permission->namespace = 'nexopos.reports.best_sales';
    $permission->description = __( 'Let you see the best_sales report' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.transactions' ] );
    $permission->name = __( 'See Transaction Report' );
    $permission->namespace = 'nexopos.reports.transactions';
    $permission->description = __( 'Let you see the transactions report' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.yearly' ] );
    $permission->name = __( 'See Yearly Sales' );
    $permission->namespace = 'nexopos.reports.yearly';
    $permission->description = __( 'Allow to see the yearly sales.' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.customers' ] );
    $permission->name = __( 'See Customers' );
    $permission->namespace = 'nexopos.reports.customers';
    $permission->description = __( 'Allow to see the customers' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.inventory' ] );
    $permission->name = __( 'See Inventory Tracking' );
    $permission->namespace = 'nexopos.reports.inventory';
    $permission->description = __( 'Allow to see the inventory' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.customers-statement' ] );
    $permission->name = __( 'See Customers Statement' );
    $permission->namespace = 'nexopos.reports.customers-statement';
    $permission->description = __( 'Allow to see the customers statement.' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.payment-types' ] );
    $permission->name = __( 'Read Sales by Payment Types' );
    $permission->namespace = 'nexopos.reports.payment-types';
    $permission->description = __( 'Let the user read the report that shows sales by payment types.' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.low-stock' ] );
    $permission->name = __( 'Read Low Stock Report' );
    $permission->namespace = 'nexopos.reports.low-stock';
    $permission->description = __( 'Let the user read the report that shows low stock.' );
    $permission->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.reports.stock-history' ] );
    $permission->name = __( 'Read Stock History' );
    $permission->namespace = 'nexopos.reports.stock-history';
    $permission->description = __( 'Let the user read the stock history report.' );
    $permission->save();
}
