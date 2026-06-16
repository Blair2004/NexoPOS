<?php

use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware( NsRestrictMiddleware::arguments( 'nexopos.read.categories' ) )->group( function () {
    Route::get( 'categories/{id?}', [ CategoryController::class, 'get' ] )->where( [ 'id' => '[0-9]+' ] )->name( 'ns.dashboard.products-categories' );
    Route::get( 'categories/{id?}/products', [ CategoryController::class, 'getCategoriesProducts' ] )->where( [ 'id' => '[0-9]+' ] )->name( 'ns.dashboard.products-categories.products' );
    Route::get( 'categories/{id?}/variations', [ CategoryController::class, 'getCategoriesVariations' ] )->where( [ 'id' => '[0-9]+' ] )->name( 'ns.dashboard.products-categories.products-variations' );
    Route::get( 'categories/pos/{id?}', [ CategoryController::class, 'getCategories' ] )->name( 'ns.dashboard.pos-categories' );
} );

Route::post( 'categories', [ CategoryController::class, 'post' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.create.categories' ) )->name( 'ns.dashboard.products-categories.create' );
Route::put( 'categories/{id}', [ CategoryController::class, 'put' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.update.categories' ) )->name( 'ns.dashboard.products-categories.update' );
Route::delete( 'categories/{id}', [ CategoryController::class, 'delete' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.delete.categories' ) )->name( 'ns.dashboard.products-categories.delete' );

Route::post( 'categories/reorder', [ CategoryController::class, 'reorderCategories' ] )->middleware(
    NsRestrictMiddleware::arguments( 'nexopos.update.categories' )
)->name( 'ns.dashboard.products-categories.reorder' );
