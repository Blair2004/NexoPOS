<?php
Route::get( 'procurements/{id?}', 'ProcurementController@list' )->where( 'id', '[0-9]+');
Route::get( 'procurements/{id}/products', 'ProcurementController@procurementProducts' );
Route::get( 'procurements/{id}/reset', 'ProcurementController@resetProcurement' );
Route::get( 'procurements/{id}/refresh', 'ProcurementController@refreshProcurement' );

Route::post( 'procurements/{id}/products', 'ProcurementController@procure' );
Route::post( 'procurements', 'ProcurementController@create' );

Route::put( 'procurements/{id}', 'ProcurementController@edit' );
Route::put( 'procurements/{id}/products/{product_id}', 'ProcurementController@editProduct' );
Route::put( 'procurements/{id}/products', 'ProcurementController@bulkUpdateProducts' );

Route::delete( 'procurements/{id}/products/{product_id}', 'ProcurementController@deleteProcurementProduct' );
Route::delete( 'procurements/{id}', 'ProcurementController@deleteProcurement' )->where('id', '[0-9]+');