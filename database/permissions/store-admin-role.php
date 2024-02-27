<?php

use App\Models\Permission;
use App\Models\Role;

$storeAdmin = Role::firstOrNew( [ 'namespace' => 'nexopos.store.administrator' ] );
$storeAdmin->name = __( 'Store Administrator' );
$storeAdmin->namespace = 'nexopos.store.administrator';
$storeAdmin->locked = true;
$storeAdmin->description = __( 'Has a control over an entire store of NexoPOS.' );
$storeAdmin->save();

$storeAdmin->addPermissions( [ 'read.dashboard' ] );
$storeAdmin->addPermissions( Permission::includes( '.transactions' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.cash-flow-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.transactions-accounts' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.categories' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.customers' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.customers-groups' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.coupons' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.orders' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.procurements' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.providers' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.products' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.products-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.products-adjustments' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.products-units' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.registers' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.registers-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.rewards' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.reports.' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.stores' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.taxes' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.trucks' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.units' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.manage-payments-types' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '.pos' )->get()->map( fn( $permission ) => $permission->namespace ) );
$storeAdmin->addPermissions( Permission::includes( '-widget' )->get()->map( fn( $permission ) => $permission->namespace ) );
