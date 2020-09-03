<?php

use Illuminate\Support\Facades\Route;

Route::get( 'providers', 'Dashboard\ProvidersController@list' );
Route::post( 'providers', 'Dashboard\ProvidersController@create' );
Route::put( 'providers/{id}', 'Dashboard\ProvidersController@edit' );
Route::get( 'providers/{id}/procurements', 'Dashboard\ProvidersController@providerProcurements' );
Route::get( 'providers/{id}', 'Dashboard\ProvidersController@getSingleProvider' );
Route::delete( 'providers/{id}', 'Dashboard\ProvidersController@deleteProvider' );