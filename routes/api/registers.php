<?php
Route::get( 'registers/{id?}', 'Dashboard\RegistersController@getRegister' );
Route::get( 'registers/{id}/history', 'Dashboard\RegistersController@getHistory' );
Route::get( 'registers/{id}/orders', 'Dashboard\RegistersController@getRegisterOrders' );
Route::put( 'registers/{id}', 'Dashboard\RegistersController@editRegister' );
Route::post( 'registers', 'Dashboard\RegistersController@create' );
Route::delete( 'registers/{id}', 'Dashboard\RegistersController@deleteRegister' );
Route::delete( 'registers/{id}/history/{history_id}', 'Dashboard\RegistersController@deleteRegisterHistory' );