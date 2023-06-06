<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $transaction = Permission::firstOrNew([ 'namespace' => 'nexopos.create.expenses' ]);
    $transaction->name = __( 'Create Transaction' );
    $transaction->namespace = 'nexopos.create.transactions';
    $transaction->description = __( 'Let the user create transactions' );
    $transaction->save();

    $transaction = Permission::firstOrNew([ 'namespace' => 'nexopos.delete.transactions' ]);
    $transaction->name = __( 'Delete Transaction' );
    $transaction->namespace = 'nexopos.delete.transactions';
    $transaction->description = __( 'Let the user delete transactions' );
    $transaction->save();

    $transaction = Permission::firstOrNew([ 'namespace' => 'nexopos.update.transactions' ]);
    $transaction->name = __( 'Update Transaction' );
    $transaction->namespace = 'nexopos.update.transactions';
    $transaction->description = __( 'Let the user update transactions' );
    $transaction->save();

    $transaction = Permission::firstOrNew([ 'namespace' => 'nexopos.read.transactions' ]);
    $transaction->name = __( 'Read Transaction' );
    $transaction->namespace = 'nexopos.read.transactions';
    $transaction->description = __( 'Let the user read transactions' );
    $transaction->save();

    $readCashFlowHistory = Permission::firstOrNew([ 'namespace' => 'nexopos.read.cash-flow-history' ]);
    $readCashFlowHistory->name = __( 'Read Cash Flow History' );
    $readCashFlowHistory->namespace = 'nexopos.read.cash-flow-history';
    $readCashFlowHistory->description = __( 'Allow to the Cash Flow History.' );
    $readCashFlowHistory->save();

    $deleteCashFlowHistory = Permission::firstOrNew([ 'namespace' => 'nexopos.delete.cash-flow-history' ]);
    $deleteCashFlowHistory->name = __( 'Delete Cash Flow History' );
    $deleteCashFlowHistory->namespace = 'nexopos.delete.cash-flow-history';
    $deleteCashFlowHistory->description = __( 'Allow to delete an Cash Flow History.' );
    $deleteCashFlowHistory->save();

    $readCashFlowHistory = Permission::withNamespaceOrNew( 'nexopos.update.cash-flow-history' );
    $readCashFlowHistory->name = __( 'Update Cash Flow History' );
    $readCashFlowHistory->namespace = 'nexopos.update.cash-flow-history';
    $readCashFlowHistory->description = __( 'Allow to the Cash Flow History.' );
    $readCashFlowHistory->save();

    $createCashFlowHistory = Permission::withNamespaceOrNew( 'nexopos.create.cash-flow-history' );
    $createCashFlowHistory->name = __( 'Create Cash Flow History' );
    $createCashFlowHistory->namespace = 'nexopos.create.cash-flow-history';
    $createCashFlowHistory->description = __( 'Allow to create a Cash Flow History.' );
    $createCashFlowHistory->save();
}
