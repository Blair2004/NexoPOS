<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $expensesCategories                 =   new Permission;
    $expensesCategories->name           =   __( 'Create Expenses Categories' );
    $expensesCategories->namespace      =   'nexopos.create.expenses-categories';
    $expensesCategories->description    =   __( 'Let the user create expenses-categories' );
    $expensesCategories->save();

    $expensesCategories                 =   new Permission;
    $expensesCategories->name           =   __( 'Delete Expenses Categories' );
    $expensesCategories->namespace      =   'nexopos.delete.expenses-categories';
    $expensesCategories->description    =   __( 'Let the user delete expenses-categories' );
    $expensesCategories->save();

    $expensesCategories                 =   new Permission;
    $expensesCategories->name           =   __( 'Update Expenses Categories' );
    $expensesCategories->namespace      =   'nexopos.update.expenses-categories';
    $expensesCategories->description    =   __( 'Let the user update expenses-categories' );
    $expensesCategories->save();

    $expensesCategories                 =   new Permission;
    $expensesCategories->name           =   __( 'Read Expenses Categories' );
    $expensesCategories->namespace      =   'nexopos.read.expenses-categories';
    $expensesCategories->description    =   __( 'Let the user read expenses-categories' );
    $expensesCategories->save();
}