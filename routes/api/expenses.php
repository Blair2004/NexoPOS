<?php
Route::get( 'expenses/{id?}', 'ExpensesController@get' )->where( 'id', '[0-9]+');
Route::get( 'expenses-categories/{id?}', 'ExpensesController@getExpensesCategories' )->where('id', '[0-9]+');
Route::get( 'expenses-categories/{id}/expenses', 'ExpensesController@getCategoryExpenses' );
Route::post( 'expenses', 'ExpensesController@post' );
Route::post( 'expenses-categories', 'ExpensesController@postExpenseCategory' );
Route::put( 'expenses/{id}', 'ExpensesController@put' )->where( 'id', '[0-9]+');
Route::put( 'expenses-categories/{id}', 'ExpensesController@putExpenseCategory' )->where( 'id', '[0-9]+');
Route::delete( 'expenses/{id}', 'ExpensesController@delete' )->where('id', '[0-9]+');
Route::delete( 'expenses-categories/{id}', 'ExpensesController@deleteCategory' )->where('id', '[0-9]+');
