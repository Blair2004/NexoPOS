<?php

use App\Http\Controllers\Dashboard\ExpensesCategoriesController;
use App\Http\Controllers\Dashboard\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get( '/accounting/transactions', [ TransactionController::class, 'listTransactions' ])->name( ns()->routeName( 'ns.dashboard.transactions' ) );
Route::get( '/accounting/transactions/create', [ TransactionController::class, 'createTransaction' ])->name( ns()->routeName( 'ns.dashboard.transactions.create' ) );
Route::get( '/accounting/transactions/edit/{transaction}', [ TransactionController::class, 'editTransaction' ])->name( ns()->routeName( 'ns.dashboard.transactions.edit' ) );
Route::get( '/accounting/transactions/history/{transaction}', [ TransactionController::class, 'getTransactionHistory' ])->name( ns()->routeName( 'ns.dashboard.transactions.history' ) );
Route::get( '/accounting/cash-flow/history', [ TransactionController::class, 'cashFlowHistory' ])->name( ns()->routeName( 'ns.dashboard.cash-flow.history' ) );

Route::get( '/accounting/accounts', [ ExpensesCategoriesController::class, 'listExpensesCategories' ])->name( ns()->routeName( 'ns.dashboard.accounting' ) );
Route::get( '/accounting/accounts/create', [ ExpensesCategoriesController::class, 'createExpenseCategory' ])->name( ns()->routeName( 'ns.dashboard.accounting.create' ) );
Route::get( '/accounting/accounts/edit/{category}', [ ExpensesCategoriesController::class, 'editExpenseCategory' ])->name( ns()->routeName( 'ns.dashboard.accounting.edit' ) );
