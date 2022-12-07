<?php

use App\Models\Permission;
use App\Models\Role;
use App\Widgets\ProfileWidget;

$storeDriver = Role::firstOrNew([ 'namespace' => 'nexopos.store.driver' ]);
$storeDriver->name = __( 'Store Driver' );
$storeDriver->namespace = 'nexopos.store.driver';
$storeDriver->locked = true;
$storeDriver->description = __( 'Has a control over the shipping and delivery of orders.' );
$storeDriver->save();

$storeDriver->addPermissions([ 'read.dashboard' ]);
$storeDriver->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeDriver->addPermissions( Permission::includes( '.orders' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeDriver->addPermissions( Permission::whereIn( 'namespace', [
    ( new ProfileWidget )->getPermission()
])->get()->map( fn( $permission ) => $permission->namespace ) );