<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $taxes = Permission::firstOrNew( [ 'namespace' => 'nexopos.create.taxes' ] );
    $taxes->name = __( 'Create Taxes' );
    $taxes->namespace = 'nexopos.create.taxes';
    $taxes->description = __( 'Let the user create taxes' );
    $taxes->save();

    $taxes = Permission::firstOrNew( [ 'namespace' => 'nexopos.delete.taxes' ] );
    $taxes->name = __( 'Delete Taxes' );
    $taxes->namespace = 'nexopos.delete.taxes';
    $taxes->description = __( 'Let the user delete taxes' );
    $taxes->save();

    $taxes = Permission::firstOrNew( [ 'namespace' => 'nexopos.update.taxes' ] );
    $taxes->name = __( 'Update Taxes' );
    $taxes->namespace = 'nexopos.update.taxes';
    $taxes->description = __( 'Let the user update taxes' );
    $taxes->save();

    $taxes = Permission::firstOrNew( [ 'namespace' => 'nexopos.read.taxes' ] );
    $taxes->name = __( 'Read Taxes' );
    $taxes->namespace = 'nexopos.read.taxes';
    $taxes->description = __( 'Let the user read taxes' );
    $taxes->save();
}
