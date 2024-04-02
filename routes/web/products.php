<?php

use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\ProductsController;
use Illuminate\Support\Facades\Route;

Route::get( '/products', [ ProductsController::class, 'listProducts' ] )->name( ns()->routeName( 'ns.dashboard.products' ) );
Route::get( '/products/create', [ ProductsController::class, 'createProduct' ] )->name( ns()->routeName( 'ns.dashboard.products.create' ) );
Route::get( '/products/stock-adjustment', [ ProductsController::class, 'showStockAdjustment' ] )->name( ns()->routeName( 'ns.dashboard.products.stock-adjustment' ) );
Route::get( '/products/print-labels', [ ProductsController::class, 'printLabels' ] )->name( ns()->routeName( 'ns.dashboard.products.print-labels' ) );
Route::get( '/products/edit/{product}', [ ProductsController::class, 'editProduct' ] )->name( ns()->routeName( 'ns.products-edit' ) ); // @todo update
Route::get( '/products/{product}/units', [ ProductsController::class, 'productUnits' ] )->name( ns()->routeName( 'ns.dashboard.products.units' ) );
Route::get( '/products/{product}/history', [ ProductsController::class, 'productHistory' ] )->name( ns()->routeName( 'ns.dashboard.products.history' ) );
Route::get( '/products/categories', [ CategoryController::class, 'listCategories' ] )->name( ns()->routeName( 'ns.dashboard.products.categories' ) );
Route::get( '/products/categories/create', [ CategoryController::class, 'createCategory' ] )->name( ns()->routeName( 'ns.dashboard.products.categories.create' ) );
Route::get( '/products/categories/edit/{category}', [ CategoryController::class, 'editCategory' ] )->name( ns()->routeName( 'ns.dashboard.categories.edit' ) );
Route::get( '/products/categories/compute-products/{category}', [ CategoryController::class, 'computeCategoryProducts' ] )->name( ns()->routeName( 'ns.dashboard.products.categories.compute' ) );
Route::get( '/products/stock-flow-records', [ CategoryController::class, 'showStockFlowCrud' ] )->name( ns()->routeName( 'ns.dashboard.products.stock-flow-records' ) );
