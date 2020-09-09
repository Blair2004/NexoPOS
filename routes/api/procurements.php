<?php

use Illuminate\Support\Facades\Route;

Route::get( 'procurements/{id?}', 'Dashboard\ProcurementController@list' )->where( 'id', '[0-9]+');
Route::get( 'procurements/{id}/products', 'Dashboard\ProcurementController@procurementProducts' );
Route::get( 'procurements/{id}/reset', 'Dashboard\ProcurementController@resetProcurement' );
Route::get( 'procurements/{id}/refresh', 'Dashboard\ProcurementController@refreshProcurement' );

Route::post( 'procurements/{id}/products', 'Dashboard\ProcurementController@procure' );
Route::post( 'procurements', 'Dashboard\ProcurementController@create' );

Route::put( 'procurements/{id}', 'Dashboard\ProcurementController@edit' );
Route::put( 'procurements/{id}/products/{product_id}', 'Dashboard\ProcurementController@editProduct' );
Route::put( 'procurements/{id}/products', 'Dashboard\ProcurementController@bulkUpdateProducts' );

Route::delete( 'procurements/{id}/products/{product_id}', 'Dashboard\ProcurementController@deleteProcurementProduct' );
Route::delete( 'procurements/{id}', 'Dashboard\ProcurementController@deleteProcurement' )->where('id', '[0-9]+');

Route::post( 'procurements', 'Dashboard\ProcurementController@makeProcurement' );