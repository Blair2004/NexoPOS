<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $manageDelivery = Permission::firstOrNew( [ 'namespace' => 'nexopos.deliver.orders' ] );
    $manageDelivery->name = __( 'Manage Delivery' );
    $manageDelivery->namespace = 'nexopos.deliver.orders';
    $manageDelivery->description = __( 'Let the user manage deliery for an order' );
    $manageDelivery->save();

    $createDrivers = Permission::firstOrNew( [ 'namespace' => 'nexopos.create.drivers' ] );
    $createDrivers->name = __( 'Create Drivers' );
    $createDrivers->namespace = 'nexopos.create.drivers';
    $createDrivers->description = __( 'Let the user create drivers' );
    $createDrivers->save();

    $deleteDrivers = Permission::firstOrNew( [ 'namespace' => 'nexopos.delete.drivers' ] );
    $deleteDrivers->name = __( 'Delete Drivers' );
    $deleteDrivers->namespace = 'nexopos.delete.drivers';
    $deleteDrivers->description = __( 'Let the user delete drivers' );
    $deleteDrivers->save();

    $updateDrivers = Permission::firstOrNew( [ 'namespace' => 'nexopos.update.drivers' ] );
    $updateDrivers->name = __( 'Update Drivers' );
    $updateDrivers->namespace = 'nexopos.update.drivers';
    $updateDrivers->description = __( 'Let the user update drivers' );
    $updateDrivers->save();

    $readDrivers = Permission::firstOrNew( [ 'namespace' => 'nexopos.read.drivers' ] );
    $readDrivers->name = __( 'Read Drivers' );
    $readDrivers->namespace = 'nexopos.read.drivers';
    $readDrivers->description = __( 'Let the user read drivers' );
    $readDrivers->save();
}