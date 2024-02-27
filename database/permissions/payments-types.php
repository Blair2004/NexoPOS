<?php

use App\Models\Permission;

$permission = Permission::firstOrNew( [ 'namespace' => 'nexopos.manage-payments-types' ] );
$permission->namespace = 'nexopos.manage-payments-types';
$permission->name = __( 'Manage Order Payments' );
$permission->description = __( 'Allow to create, update and delete payments type.' );
$permission->save();
