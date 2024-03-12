<?php

use App\Models\Permission;
use App\Models\Role;

$admin = Role::firstOrNew( [ 'namespace' => 'admin' ] );
$admin->name = __( 'Administrator' );
$admin->namespace = 'admin';
$admin->locked = true;
$admin->description = __( 'Master role which can perform all actions like create users, install/update/delete modules and much more.' );
$admin->save();
$admin->addPermissions( [
    'create.users',
    'read.users',
    'update.users',
    'delete.users',
    'create.roles',
    'read.roles',
    'update.roles',
    'delete.roles',
    'update.core',
    'manage.profile',
    'manage.options',
    'manage.modules',
    'read.dashboard',
] );

$admin->addPermissions( Permission::includes( '.transactions' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.cash-flow-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.transactions-accounts' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.medias' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.categories' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.customers' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.customers-groups' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.coupons' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.orders' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.procurements' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.providers' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.products' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.products-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.products-adjustments' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.products-units' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.profile' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.registers' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.registers-history' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.rewards' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.reports.' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.stores' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.taxes' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.trucks' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.units' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.manage-payments-types' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '.pos' )->get()->map( fn( $permission ) => $permission->namespace ) );
$admin->addPermissions( Permission::includes( '-widget' )->get()->map( fn( $permission ) => $permission->namespace ) );
