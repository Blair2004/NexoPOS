<?php

use App\Http\Controllers\Dashboard\CategoryController;
use Illuminate\Support\Facades\Route;

Route::get( 'categories/{id?}', 'Dashboard\CategoryController@get' )->where([ 'id' => '[0-9]+' ]);
Route::get( 'categories/{id?}/products', 'Dashboard\CategoryController@getCategoriesProducts' )->where([ 'id' => '[0-9]+' ]);
Route::get( 'categories/{id?}/variations', 'Dashboard\CategoryController@getCategoriesVariations' )->where([ 'id' => '[0-9]+' ]);
Route::get( 'categories/schema', 'Dashboard\CategoryController@schema' );
Route::post( 'categories', 'Dashboard\CategoryController@post' );
Route::put( 'categories/{id}', 'Dashboard\CategoryController@put' );
Route::delete( 'categories/{id}', 'Dashboard\CategoryController@delete' );

Route::get( 'categories/pos/{id?}', [ CategoryController::class, 'getCategories' ]);