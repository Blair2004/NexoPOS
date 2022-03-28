<?php

use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\ProductsController;
use Illuminate\Support\Facades\Route;

Route::get( '/products', [ ProductsController::class, 'listProducts' ]);
Route::get( '/products/create', [ ProductsController::class, 'createProduct' ]);
Route::get( '/products/stock-adjustment', [ ProductsController::class, 'showStockAdjustment' ]);
Route::get( '/products/print-labels', [ ProductsController::class, 'printLabels' ]);
Route::get( '/products/edit/{product}', [ ProductsController::class, 'editProduct' ])->name( ns()->routeName( 'ns.products-edit' ) );
Route::get( '/products/{product}/units', [ ProductsController::class, 'productUnits' ]);
Route::get( '/products/{product}/history', [ ProductsController::class, 'productHistory' ]);
Route::get( '/products/categories', [ CategoryController::class, 'listCategories' ]);
Route::get( '/products/categories/create', [ CategoryController::class, 'createCategory' ]);
Route::get( '/products/categories/edit/{category}', [ CategoryController::class, 'editCategory' ]);
Route::get( '/products/categories/compute-products/{category}', [ CategoryController::class, 'computeCategoryProducts' ]);