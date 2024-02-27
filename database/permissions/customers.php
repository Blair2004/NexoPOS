<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $customers = Permission::firstOrNew( [ 'namespace' => 'nexopos.create.customers' ] );
    $customers->name = __( 'Create Customers' );
    $customers->namespace = 'nexopos.create.customers';
    $customers->description = __( 'Let the user create customers.' );
    $customers->save();

    $customers = Permission::firstOrNew( [ 'namespace' => 'nexopos.delete.customers' ] );
    $customers->name = __( 'Delete Customers' );
    $customers->namespace = 'nexopos.delete.customers';
    $customers->description = __( 'Let the user delete customers.' );
    $customers->save();

    $customers = Permission::firstOrNew( [ 'namespace' => 'nexopos.update.customers' ] );
    $customers->name = __( 'Update Customers' );
    $customers->namespace = 'nexopos.update.customers';
    $customers->description = __( 'Let the user update customers.' );
    $customers->save();

    $customers = Permission::firstOrNew( [ 'namespace' => 'nexopos.read.customers' ] );
    $customers->name = __( 'Read Customers' );
    $customers->namespace = 'nexopos.read.customers';
    $customers->description = __( 'Let the user read customers.' );
    $customers->save();

    $customers = Permission::firstOrNew( [ 'namespace' => 'nexopos.import.customers' ] );
    $customers->name = __( 'Import Customers' );
    $customers->namespace = 'nexopos.import.customers';
    $customers->description = __( 'Let the user import customers.' );
    $customers->save();

    $permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.customers.manage-account-history' ] );
    $permission->namespace = 'nexopos.customers.manage-account-history';
    $permission->name = __( 'Manage Customer Account History' );
    $permission->description = __( 'Can add, deduct amount from each customers account.' );
    $permission->save();
}
