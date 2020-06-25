<?php
Route::get( 'categories/{id?}', 'CategoryController@get' )->where([ 'id' => '[0-9]+' ]);
Route::get( 'categories/{id?}/products', 'CategoryController@getCategoriesProducts' )->where([ 'id' => '[0-9]+' ]);
Route::get( 'categories/{id?}/variations', 'CategoryController@getCategoriesVariations' )->where([ 'id' => '[0-9]+' ]);
Route::get( 'categories/schema', 'CategoryController@schema' );
Route::post( 'categories', 'CategoryController@post' );
Route::put( 'categories/{id}', 'CategoryController@put' );
Route::delete( 'categories/{id}', 'CategoryController@delete' );