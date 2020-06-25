<?php
Route::get( 'providers', 'ProviderController@list' );
Route::post( 'providers', 'ProviderController@create' );
Route::put( 'providers/{id}', 'ProviderController@edit' );
Route::get( 'providers/{id}/procurements', 'ProviderController@providerProcurements' );
Route::get( 'providers/{id}', 'ProviderController@getSingleProvider' );
Route::delete( 'providers/{id}', 'ProviderController@deleteProvider' );