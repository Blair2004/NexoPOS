<?php

use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::get( 'categories/{id?}', [ CategoryController::class, 'get' ] )->where( [ 'id' => '[0-9]+' ] );
Route::get( 'categories/{id?}/products', [ CategoryController::class, 'getCategoriesProducts' ] )->where( [ 'id' => '[0-9]+' ] );
Route::get( 'categories/{id?}/variations', [ CategoryController::class, 'getCategoriesVariations' ] )->where( [ 'id' => '[0-9]+' ] );
Route::post( 'categories', [ CategoryController::class, 'post' ] );
Route::put( 'categories/{id}', [ CategoryController::class, 'put' ] );
Route::delete( 'categories/{id}', [ CategoryController::class, 'delete' ] );

Route::get( 'categories/pos/{id?}', [ CategoryController::class, 'getCategories' ] );

Route::post( 'categories/reorder', [ CategoryController::class, 'reorderCategories' ] )->middleware( 
    NsRestrictMiddleware::arguments( 'nexopos.update.categories' ) 
);
