<?php

use Illuminate\Support\Facades\Route;

Route::post( 'taxes', 'Dashboard\TaxesController@post' );
Route::put( 'taxes/{id}', 'Dashboard\TaxesController@put' );
Route::delete( 'taxes/{id}', 'Dashboard\TaxesController@delete' );

Route::get( 'taxes/{id?}', 'Dashboard\TaxesController@get' )->where([ 'id' => '[0-9]+' ]);
Route::get( 'taxes/groups/{id?}', 'Dashboard\TaxesController@getTaxGroup' );