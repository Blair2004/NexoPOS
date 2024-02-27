<?php

use App\Http\Controllers\Dashboard\ProductsController;
use Illuminate\Support\Facades\Route;

Route::get( 'products', [ ProductsController::class, 'getProduts' ] );
Route::get( 'products/all/variations', [ ProductsController::class, 'getAllVariations' ] );
Route::get( 'products/{identifier}', [ ProductsController::class, 'singleProduct' ] );
Route::get( 'products/{identifier}/variations', [ ProductsController::class, 'getProductVariations' ] );
Route::get( 'products/{identifier}/refresh-prices', [ ProductsController::class, 'refreshPrices' ] );
Route::get( 'products/{identifier}/reset', [ ProductsController::class, 'reset' ] );
Route::get( 'products/{identifier}/history', [ ProductsController::class, 'history' ] );
Route::get( 'products/{identifier}/units', [ ProductsController::class, 'units' ] );
Route::get( 'products/{product}/units/{unit}/quantity', [ ProductsController::class, 'getUnitQuantity' ] );
Route::get( 'products/{product}/units/quantities', [ ProductsController::class, 'getUnitQuantities' ] );
Route::get( 'products/{product}/procurements', [ ProductsController::class, 'getProcuredProducts' ] );
Route::get( 'products/search/using-barcode/{product}', [ ProductsController::class, 'searchUsingArgument' ] );

Route::delete( 'products/{identifier}', [ ProductsController::class, 'deleteProduct' ] );
Route::delete( 'products/units/quantity/{unitQuantity}', [ ProductsController::class, 'deleteUnitQuantity' ] );
Route::delete( 'products/all/variations', [ ProductsController::class, 'deleteAllVariations' ] );
Route::delete( 'products/{identifier}/variations/{variation_id}', [ ProductsController::class, 'deleteSingleVariation' ] );
Route::delete( 'products', [ ProductsController::class, 'deleteAllProducts' ] );

Route::post( 'products', [ ProductsController::class, 'saveProduct' ] );
Route::post( 'products/search', [ ProductsController::class, 'searchProduct' ] );
Route::post( 'products/adjustments', [ ProductsController::class, 'createAdjustment' ] );
Route::post( 'products/{identifier}/variations/{variation_id}', [ ProductsController::class, 'createSingleVariation' ] );
Route::post( 'products/{product}/units/conversion', [ ProductsController::class, 'convertUnits' ] );

Route::put( 'products/{identifier}/variations/{variation_id}', [ ProductsController::class, 'editSingleVariation' ] );
Route::put( 'products/{product}', [ ProductsController::class, 'updateProduct' ] );
