<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $transactionAccount = Permission::firstOrNew( [ 'namespace' => 'nexopos.create.transactions-account' ] );
    $transactionAccount->name = __( 'Create Transaction Account' );
    $transactionAccount->namespace = 'nexopos.create.transactions-account';
    $transactionAccount->description = __( 'Let the user create transactions account' );
    $transactionAccount->save();

    $transactionAccount = Permission::firstOrNew( [ 'namespace' => 'nexopos.delete.transactions-account' ] );
    $transactionAccount->name = __( 'Delete Transactions Account' );
    $transactionAccount->namespace = 'nexopos.delete.transactions-account';
    $transactionAccount->description = __( 'Let the user delete Transaction Account' );
    $transactionAccount->save();

    $transactionAccount = Permission::firstOrNew( [ 'namespace' => 'nexopos.update.transactions-account' ] );
    $transactionAccount->name = __( 'Update Transactions Account' );
    $transactionAccount->namespace = 'nexopos.update.transactions-account';
    $transactionAccount->description = __( 'Let the user update Transaction Account' );
    $transactionAccount->save();

    $transactionAccount = Permission::firstOrNew( [ 'namespace' => 'nexopos.read.transactions-account' ] );
    $transactionAccount->name = __( 'Read Transactions Account' );
    $transactionAccount->namespace = 'nexopos.read.transactions-account';
    $transactionAccount->description = __( 'Let the user read Transaction Account' );
    $transactionAccount->save();
}
