<?php

use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $expensesCategories = Permission::firstOrNew([ 'namespace' => 'nexopos.create.expenses-categories' ]);
    $expensesCategories->name = __( 'Create Expenses Categories' );
    $expensesCategories->namespace = 'nexopos.create.expenses-categories';
    $expensesCategories->description = __( 'Let the user create expenses-categories' );
    $expensesCategories->save();

    $expensesCategories = Permission::firstOrNew([ 'namespace' => 'nexopos.delete.expenses-categories' ]);
    $expensesCategories->name = __( 'Delete Expenses Categories' );
    $expensesCategories->namespace = 'nexopos.delete.expenses-categories';
    $expensesCategories->description = __( 'Let the user delete expenses-categories' );
    $expensesCategories->save();

    $expensesCategories = Permission::firstOrNew([ 'namespace' => 'nexopos.update.expenses-categories' ]);
    $expensesCategories->name = __( 'Update Expenses Categories' );
    $expensesCategories->namespace = 'nexopos.update.expenses-categories';
    $expensesCategories->description = __( 'Let the user update expenses-categories' );
    $expensesCategories->save();

    $expensesCategories = Permission::firstOrNew([ 'namespace' => 'nexopos.read.expenses-categories' ]);
    $expensesCategories->name = __( 'Read Expenses Categories' );
    $expensesCategories->namespace = 'nexopos.read.expenses-categories';
    $expensesCategories->description = __( 'Let the user read expenses-categories' );
    $expensesCategories->save();
}
