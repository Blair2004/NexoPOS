<?php
Route::get( 'registers/{id?}', 'RegistersController@getRegister' );
Route::get( 'registers/{id}/history', 'RegistersController@getHistory' );
Route::get( 'registers/{id}/orders', 'RegistersController@getRegisterOrders' );
Route::put( 'registers/{id}', 'RegistersController@editRegister' );
Route::post( 'registers', 'RegistersController@create' );
Route::delete( 'registers/{id}', 'RegistersController@deleteRegister' );
Route::delete( 'registers/{id}/history/{history_id}', 'RegistersController@deleteRegisterHistory' );