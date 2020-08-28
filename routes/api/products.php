<?php

use Illuminate\Support\Facades\Route;

Route::get( 'products', 'Dashboard\ProductsController@getProduts' );
Route::get( 'products/all/variations', 'Dashboard\ProductsController@getAllVariations' );
Route::get( 'products/{identifier}', 'Dashboard\ProductsController@singleProduct' );
Route::get( 'products/{identifier}/variations', 'Dashboard\ProductsController@getProductVariations' );
Route::get( 'products/{identifier}/refresh-prices', 'Dashboard\ProductsController@refreshPrices' );
Route::get( 'products/{identifier}/reset', 'Dashboard\ProductsController@reset' );
Route::get( 'products/{identifier}/history', 'Dashboard\ProductsController@history' );
Route::get( 'products/{identifier}/units', 'Dashboard\ProductsController@units' );

Route::delete( 'products/{identifier}', 'Dashboard\ProductsController@deleteProduct' );
Route::delete( 'products/all/variations', 'Dashboard\ProductsController@deleteAllVariations' );
Route::delete( 'products/{identifier}/variations/{variation_id}', 'Dashboard\ProductsController@deleteSingleVariation' );
Route::delete( 'products', 'Dashboard\ProductsController@deleteAllProducts' );

Route::post( 'products', 'Dashboard\ProductsController@saveProduct' );
Route::post( 'products/{identifier}/variations/{variation_id}', 'Dashboard\ProductsController@createSingleVariation' );

Route::put( 'products/{identifier}/variations/{variation_id}', 'Dashboard\ProductsController@editSingleVariation' );
Route::put( 'products/{id}', 'Dashboard\ProductsController@updateProduct' );