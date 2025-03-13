<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $manageDelivery = Permission::firstOrNew( [ 'namespace' => 'nexopos.deliver.orders' ] );
    $manageDelivery->name = __( 'Manage Delivery' );
    $manageDelivery->namespace = 'nexopos.deliver.orders';
    $manageDelivery->description = __( 'Let the user manage deliery for an order' );
    $manageDelivery->save();
}