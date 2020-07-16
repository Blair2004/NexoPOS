<?php
Route::get( 'transfer/{id?}', 'Dashboard\TransfersController@list' );
Route::post( 'transfer', 'Dashboard\TransfersController@create' );
Route::put( 'transfer/{id}', 'Dashboard\TransfersController@edit' );
Route::delete( 'transfer/{id}', 'Dashboard\TransfersController@delete' );