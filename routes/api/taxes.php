<?php
Route::get( 'taxes/{id?}', 'TaxesController@get' );
Route::post( 'taxes', 'TaxesController@post' );
Route::put( 'taxes/{id}', 'TaxesController@put' );
Route::delete( 'taxes/{id}', 'TaxesController@delete' );
Route::get( 'taxes/{id}/sub-taxes', 'TaxesController@subTaxes' );