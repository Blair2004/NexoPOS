<?php
Route::get( 'products', 'Dashboard\ProductController@getProduts' );
Route::get( 'products/all/variations', 'Dashboard\ProductController@getAllVariations' );
Route::get( 'products/{identifier}', 'Dashboard\ProductController@singleProduct' );
Route::get( 'products/{identifier}/variations', 'Dashboard\ProductController@getProductVariations' );
Route::get( 'products/{identifier}/refresh-prices', 'Dashboard\ProductController@refreshPrices' );
Route::get( 'products/{identifier}/reset', 'Dashboard\ProductController@reset' );
Route::get( 'products/{identifier}/history', 'Dashboard\ProductController@history' );
Route::get( 'products/{identifier}/units', 'Dashboard\ProductController@units' );

Route::delete( 'products/{identifier}', 'Dashboard\ProductController@deleteProduct' );
Route::delete( 'products/all/variations', 'Dashboard\ProductController@deleteAllVariations' );
Route::delete( 'products/{identifier}/variations/{variation_id}', 'Dashboard\ProductController@deleteSingleVariation' );
Route::delete( 'products', 'Dashboard\ProductController@deleteAllProducts' );

Route::post( 'products', 'Dashboard\ProductController@saveProduct' );
Route::post( 'products/{identifier}/variations/{variation_id}', 'Dashboard\ProductController@createSingleVariation' );

Route::put( 'products/{identifier}/variations/{variation_id}', 'Dashboard\ProductController@editSingleVariation' );
Route::put( 'products/{id}', 'Dashboard\ProductController@updateProduct' );