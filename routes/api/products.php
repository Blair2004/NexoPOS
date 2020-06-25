<?php
Route::get( 'products', 'ProductController@getProduts' );
Route::get( 'products/all/variations', 'ProductController@getAllVariations' );
Route::get( 'products/{identifier}', 'ProductController@singleProduct' );
Route::get( 'products/{identifier}/variations', 'ProductController@getProductVariations' );
Route::get( 'products/{identifier}/refresh-prices', 'ProductController@refreshPrices' );
Route::get( 'products/{identifier}/reset', 'ProductController@reset' );
Route::get( 'products/{identifier}/history', 'ProductController@history' );
Route::get( 'products/{identifier}/units', 'ProductController@units' );

Route::delete( 'products/{identifier}', 'ProductController@deleteProduct' );
Route::delete( 'products/all/variations', 'ProductController@deleteAllVariations' );
Route::delete( 'products/{identifier}/variations/{variation_id}', 'ProductController@deleteSingleVariation' );
Route::delete( 'products', 'ProductController@deleteAllProducts' );

Route::post( 'products', 'ProductController@saveProduct' );
Route::post( 'products/{identifier}/variations/{variation_id}', 'ProductController@createSingleVariation' );

Route::put( 'products/{identifier}/variations/{variation_id}', 'ProductController@editSingleVariation' );
Route::put( 'products/{id}', 'ProductController@updateProduct' );