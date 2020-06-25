<?php
Route::get( 'stores/{id?}', 'StoresController@list' );
Route::post( 'stores', 'StoresController@create' );
Route::put( 'stores/{id}', 'StoresController@edit' );
Route::delete( 'stores/{id}', 'StoresController@delete' );