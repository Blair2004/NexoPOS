<?php

use App\Models\Permission;
use App\Models\Role;
use App\Widgets\ProfileWidget;

$storeCustomer = Role::firstOrNew( [ 'namespace' => 'nexopos.store.customer' ] );
$storeCustomer->name = __( 'Store Customer' );
$storeCustomer->namespace = 'nexopos.store.customer';
$storeCustomer->locked = true;
$storeCustomer->description = __( 'Can purchase orders and manage his profile.' );
$storeCustomer->save();

$storeCustomer->addPermissions( [ 'read.dashboard' ] );
$storeCustomer->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeCustomer->addPermissions( Permission::whereIn( 'namespace', [
    ( new ProfileWidget )->getPermission(),
] )->get()->map( fn( $permission ) => $permission->namespace ) );
