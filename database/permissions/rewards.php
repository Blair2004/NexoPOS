<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $reward = Permission::firstOrNew( [ 'namespace' => 'nexopos.create.rewards' ] );
    $reward->name = __( 'Create Rewards' );
    $reward->namespace = 'nexopos.create.rewards';
    $reward->description = __( 'Let the user create Rewards' );
    $reward->save();

    $reward = Permission::firstOrNew( [ 'namespace' => 'nexopos.delete.rewards' ] );
    $reward->name = __( 'Delete Rewards' );
    $reward->namespace = 'nexopos.delete.rewards';
    $reward->description = __( 'Let the user delete Rewards' );
    $reward->save();

    $reward = Permission::firstOrNew( [ 'namespace' => 'nexopos.update.rewards' ] );
    $reward->name = __( 'Update Rewards' );
    $reward->namespace = 'nexopos.update.rewards';
    $reward->description = __( 'Let the user update Rewards' );
    $reward->save();

    $reward = Permission::firstOrNew( [ 'namespace' => 'nexopos.read.rewards' ] );
    $reward->name = __( 'Read Rewards' );
    $reward->namespace = 'nexopos.read.rewards';
    $reward->description = __( 'Let the user read Rewards' );
    $reward->save();
}
