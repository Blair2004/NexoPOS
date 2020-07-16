<?php
Route::get( 'taxes/{id?}', 'Dashboard\TaxesController@get' );
Route::post( 'taxes', 'Dashboard\TaxesController@post' );
Route::put( 'taxes/{id}', 'Dashboard\TaxesController@put' );
Route::delete( 'taxes/{id}', 'Dashboard\TaxesController@delete' );
Route::get( 'taxes/{id}/sub-taxes', 'Dashboard\TaxesController@subTaxes' );