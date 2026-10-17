<?php

use App\Models\Permission;
use App\Models\Role;
use App\Widgets\ProfileWidget;

$storeCashier = Role::firstOrNew( [ 'namespace' => 'nexopos.store.cashier' ] );
$storeCashier->name = __( 'Store Cashier' );
$storeCashier->namespace = 'nexopos.store.cashier';
$storeCashier->locked = true;
$storeCashier->description = __( 'Has a control over the sale process.' );
$storeCashier->save();
$storeCashier->addPermissions( [ 'read.dashboard' ] );
$storeCashier->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeCashier->addPermissions( Permission::whereIn( 'namespace', [
    'nexopos.create.orders',
    'nexopos.update.orders',
    'nexopos.void.orders',
    'nexopos.refund.orders',
    'nexopos.make-payment.orders',
    'nexopos.read.registers',
    'nexopos.read.customers',
    'nexopos.create.orders-instaments',
    'nexopos.update.orders-instaments',
    'nexopos.read.orders-instaments',
    'nexopos.customers.manage-account-history',
] )->get()->map( fn( $permission ) => $permission->namespace ) );

$storeCashier->addPermissions( Permission::whereIn( 'namespace', [
    'nexopos.pos.access',
    'nexopos.pos.read-products',
    'nexopos.pos.read-own-orders',
    'nexopos.pos.edit-purchase-price',
    'nexopos.pos.edit-settings',
    'nexopos.pos.products-discount',
    'nexopos.pos.cart-discount',
    'nexopos.pos.delete-order-product',
] )->get()->map( fn( $permission ) => $permission->namespace ) );

$storeCashier->addPermissions( Permission::whereIn( 'namespace', [
    ( new ProfileWidget )->getPermission(),
] )->get()->map( fn( $permission ) => $permission->namespace ) );
