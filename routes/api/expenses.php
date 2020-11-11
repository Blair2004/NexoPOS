<?php

use App\Http\Controllers\Dashboard\ExpensesController;
use Illuminate\Support\Facades\Route;

Route::get( 'expenses/{id?}', [ ExpensesController::class, 'get' ])->where( 'id', '[0-9]+');
Route::get( 'expenses-categories/{id?}', [ ExpensesController::class, 'getExpensesCategories' ])->where('id', '[0-9]+');
Route::get( 'expenses-categories/{id}/expenses', [ ExpensesController::class, 'getCategoryExpenses' ]);
Route::post( 'expenses', [ ExpensesController::class, 'post' ]);
Route::post( 'expenses-categories', [ ExpensesController::class, 'postExpenseCategory' ]);
Route::put( 'expenses/{id}', [ ExpensesController::class, 'put' ])->where( 'id', '[0-9]+');
Route::put( 'expenses-categories/{id}', [ ExpensesController::class, 'putExpenseCategory' ])->where( 'id', '[0-9]+');
Route::delete( 'expenses/{id}', [ ExpensesController::class, 'delete' ])->where('id', '[0-9]+');
Route::delete( 'expenses-categories/{id}', [ ExpensesController::class, 'deleteCategory' ])->where('id', '[0-9]+');
