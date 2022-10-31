<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $expenses = Permission::firstOrNew([ 'namespace' => 'nexopos.create.expenses' ]);
    $expenses->name = __( 'Create Expenses' );
    $expenses->namespace = 'nexopos.create.expenses';
    $expenses->description = __( 'Let the user create expenses' );
    $expenses->save();

    $expenses = Permission::firstOrNew([ 'namespace' => 'nexopos.delete.expenses' ]);
    $expenses->name = __( 'Delete Expenses' );
    $expenses->namespace = 'nexopos.delete.expenses';
    $expenses->description = __( 'Let the user delete expenses' );
    $expenses->save();

    $expenses = Permission::firstOrNew([ 'namespace' => 'nexopos.update.expenses' ]);
    $expenses->name = __( 'Update Expenses' );
    $expenses->namespace = 'nexopos.update.expenses';
    $expenses->description = __( 'Let the user update expenses' );
    $expenses->save();

    $expenses = Permission::firstOrNew([ 'namespace' => 'nexopos.read.expenses' ]);
    $expenses->name = __( 'Read Expenses' );
    $expenses->namespace = 'nexopos.read.expenses';
    $expenses->description = __( 'Let the user read expenses' );
    $expenses->save();

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
