<?php

use App\Http\Controllers\Dashboard\ExpensesCategoriesController;
use App\Http\Controllers\Dashboard\ExpensesController;
use Illuminate\Support\Facades\Route;

Route::get( '/expenses', [ ExpensesController::class, 'listExpenses' ])->name( ns()->routeName( 'ns.dashboard.expenses' ) );
Route::get( '/expenses/create', [ ExpensesController::class, 'createExpense' ])->name( ns()->routeName( 'ns.dashboard.expenses.create' ) );
Route::get( '/expenses/edit/{expense}', [ ExpensesController::class, 'editExpense' ])->name( ns()->routeName( 'ns.dashboard.expenses.edit' ) );
Route::get( '/cash-flow/history', [ ExpensesController::class, 'cashFlowHistory' ])->name( ns()->routeName( 'ns.dashboard.expenses.history' ) );

Route::get( '/accounting/accounts', [ ExpensesCategoriesController::class, 'listExpensesCategories' ])->name( ns()->routeName( 'ns.dashboard.accounting' ) );
Route::get( '/accounting/accounts/create', [ ExpensesCategoriesController::class, 'createExpenseCategory' ])->name( ns()->routeName( 'ns.dashboard.accounting.create' ) );
Route::get( '/accounting/accounts/edit/{category}', [ ExpensesCategoriesController::class, 'editExpenseCategory' ])->name( ns()->routeName( 'ns.dashboard.accounting.edit' ) );
