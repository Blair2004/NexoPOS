<?php

use App\Http\Controllers\Dashboard\ExpensesCategoriesController;
use Illuminate\Support\Facades\Route;

Route::get( '/expenses', [ ExpensesController::class, 'listExpenses' ]);
Route::get( '/expenses/create', [ ExpensesController::class, 'createExpense' ]);
Route::get( '/expenses/edit/{expense}', [ ExpensesController::class, 'editExpense' ]);
Route::get( '/expenses/history', [ ExpensesController::class, 'expensesHistory' ]);

Route::get( '/expenses/categories', [ ExpensesCategoriesController::class, 'listExpensesCategories' ]);
Route::get( '/expenses/categories/create', [ ExpensesCategoriesController::class, 'createExpenseCategory' ]);
Route::get( '/expenses/categories/edit/{category}', [ ExpensesCategoriesController::class, 'editExpenseCategory' ]);