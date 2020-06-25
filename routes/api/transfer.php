<?php
Route::get( 'transfer/{id?}', 'StoresController@list' );
Route::post( 'transfer', 'StoresController@create' );
Route::put( 'transfer/{id}', 'StoresController@edit' );
Route::delete( 'transfer/{id}', 'StoresController@delete' );