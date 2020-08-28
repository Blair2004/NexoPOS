<?php
Route::get( 'providers', 'Dashboard\ProviderController@list' );
Route::post( 'providers', 'Dashboard\ProviderController@create' );
Route::put( 'providers/{id}', 'Dashboard\ProviderController@edit' );
Route::get( 'providers/{id}/procurements', 'Dashboard\ProviderController@providerProcurements' );
Route::get( 'providers/{id}', 'Dashboard\ProviderController@getSingleProvider' );
Route::delete( 'providers/{id}', 'Dashboard\ProviderController@deleteProvider' );