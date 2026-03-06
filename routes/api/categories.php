<?php

use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware( NsRestrictMiddleware::arguments( 'nexopos.read.categories' ) )->group( function () {
    Route::get( 'categories/{id?}', [ CategoryController::class, 'get' ] )->where( [ 'id' => '[0-9]+' ] );
    Route::get( 'categories/{id?}/products', [ CategoryController::class, 'getCategoriesProducts' ] )->where( [ 'id' => '[0-9]+' ] );
    Route::get( 'categories/{id?}/variations', [ CategoryController::class, 'getCategoriesVariations' ] )->where( [ 'id' => '[0-9]+' ] );
    Route::get( 'categories/pos/{id?}', [ CategoryController::class, 'getCategories' ] );
} );

Route::post( 'categories', [ CategoryController::class, 'post' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.create.categories' ) );
Route::put( 'categories/{id}', [ CategoryController::class, 'put' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.update.categories' ) );
Route::delete( 'categories/{id}', [ CategoryController::class, 'delete' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.delete.categories' ) );

Route::post( 'categories/reorder', [ CategoryController::class, 'reorderCategories' ] )->middleware(
    NsRestrictMiddleware::arguments( 'nexopos.update.categories' )
);
