<?php

use App\Http\Controllers\Dashboard\ExpensesCategoriesController;
use App\Http\Controllers\Dashboard\ExpensesController;
use Illuminate\Support\Facades\Route;

Route::get( '/expenses', [ ExpensesController::class, 'listExpenses' ]);
Route::get( '/expenses/create', [ ExpensesController::class, 'createExpense' ]);
Route::get( '/expenses/edit/{expense}', [ ExpensesController::class, 'editExpense' ]);
Route::get( '/cash-flow/history', [ ExpensesController::class, 'cashFlowHistory' ]);
Route::get( '/cash-flow/history/create', [ ExpensesController::class, 'createCashFlowHistory' ]);

Route::get( '/accounting/accounts', [ ExpensesCategoriesController::class, 'listExpensesCategories' ]);
Route::get( '/accounting/accounts/create', [ ExpensesCategoriesController::class, 'createExpenseCategory' ]);
Route::get( '/accounting/accounts/edit/{category}', [ ExpensesCategoriesController::class, 'editExpenseCategory' ]);