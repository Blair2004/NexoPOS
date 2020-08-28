<?php
Route::get( 'stores/{id?}', 'Dashboard\StoresController@list' );
Route::post( 'stores', 'Dashboard\StoresController@create' );
Route::put( 'stores/{id}', 'Dashboard\StoresController@edit' );
Route::delete( 'stores/{id}', 'Dashboard\StoresController@delete' );