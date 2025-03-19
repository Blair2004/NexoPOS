<?php

use App\Models\Permission;
use App\Models\Role;

$driverRole = Role::firstOrNew( [ 'namespace' => 'nexopos.driver' ] );
$driverRole->name = __( 'Driver' );
$driverRole->namespace = 'nexopos.driver';
$driverRole->locked = true;
$driverRole->description = __( 'Has a control over orders delivery.' );
$driverRole->save();

$driverRole->addPermissions( Permission::includes( 'deliver.orders' )->get()->map( fn( $permission ) => $permission->namespace ) );
$driverRole->addPermissions([
    'manage.profile',
    'read.dashboard',
]);