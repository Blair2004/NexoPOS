<?php
Route::get( 'expenses/{id?}', 'Dashboard\ExpensesController@get' )->where( 'id', '[0-9]+');
Route::get( 'expenses-categories/{id?}', 'Dashboard\ExpensesController@getExpensesCategories' )->where('id', '[0-9]+');
Route::get( 'expenses-categories/{id}/expenses', 'Dashboard\ExpensesController@getCategoryExpenses' );
Route::post( 'expenses', 'Dashboard\ExpensesController@post' );
Route::post( 'expenses-categories', 'Dashboard\ExpensesController@postExpenseCategory' );
Route::put( 'expenses/{id}', 'Dashboard\ExpensesController@put' )->where( 'id', '[0-9]+');
Route::put( 'expenses-categories/{id}', 'Dashboard\ExpensesController@putExpenseCategory' )->where( 'id', '[0-9]+');
Route::delete( 'expenses/{id}', 'Dashboard\ExpensesController@delete' )->where('id', '[0-9]+');
Route::delete( 'expenses-categories/{id}', 'Dashboard\ExpensesController@deleteCategory' )->where('id', '[0-9]+');
