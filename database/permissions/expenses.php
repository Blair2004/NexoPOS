<?php
use App\Models\Permission;

if ( defined( 'NEXO_CREATE_PERMISSIONS' ) ) {
    $expenses                 =   new Permission;
    $expenses->name           =   __( 'Create Expenses' );
    $expenses->namespace      =   'nexopos.create.expenses';
    $expenses->description    =   __( 'Let the user create expenses' );
    $expenses->save();

    $expenses                 =   new Permission;
    $expenses->name           =   __( 'Delete Expenses' );
    $expenses->namespace      =   'nexopos.delete.expenses';
    $expenses->description    =   __( 'Let the user delete expenses' );
    $expenses->save();

    $expenses                 =   new Permission;
    $expenses->name           =   __( 'Update Expenses' );
    $expenses->namespace      =   'nexopos.update.expenses';
    $expenses->description    =   __( 'Let the user update expenses' );
    $expenses->save();

    $expenses                 =   new Permission;
    $expenses->name           =   __( 'Read Expenses' );
    $expenses->namespace      =   'nexopos.read.expenses';
    $expenses->description    =   __( 'Let the user read expenses' );
    $expenses->save();
    
    $expenses                 =   new Permission;
    $expenses->name           =   __( 'Read Expense History' );
    $expenses->namespace      =   'nexopos.read.expenses-history';
    $expenses->description    =   __( 'Allow to the expense history.' );
    $expenses->save();

    $expenses                 =   new Permission;
    $expenses->name           =   __( 'Delete Expense History' );
    $expenses->namespace      =   'nexopos.delete.expenses-history';
    $expenses->description    =   __( 'Allow to delete an expense history.' );
    $expenses->save();

    $readHistory                 =   Permission::withNamespaceOrNew( 'nexopos.read.expenses-history' );
    $readHistory->name           =   __( 'Read Expense History' );
    $readHistory->namespace      =   'nexopos.read.expenses-history';
    $readHistory->description    =   __( 'Allow to the expense history.' );
    $readHistory->save();

    $deleteHistory                 =   Permission::withNamespaceOrNew( 'nexopos.delete.expenses-history' );
    $deleteHistory->name           =   __( 'Delete Expense History' );
    $deleteHistory->namespace      =   'nexopos.delete.expenses-history';
    $deleteHistory->description    =   __( 'Allow to delete an expense history.' );
    $deleteHistory->save();
}