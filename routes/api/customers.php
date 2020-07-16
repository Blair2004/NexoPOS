<?php
Route::delete( 'customers/{id}', 'Dashboard\CustomersController@delete' );
Route::delete( 'customers/using-email/{email}', 'Dashboard\CustomersController@deleteUsingEmail' );
Route::get( 'customers/{customer?}', 'Dashboard\CustomersController@get' )->where([ 'customer' => '[0-9]+' ]);
Route::get( 'customers/{customer}/orders', 'Dashboard\CustomersController@getOrders' );
Route::get( 'customers/{customer}/addresses', 'Dashboard\CustomersController@getAddresses' );
Route::get( 'customers/schema', 'Dashboard\CustomersController@schema' );
Route::post( 'customers', 'Dashboard\CustomersController@post' );
Route::put( 'customers/{customer}', 'Dashboard\CustomersController@put' );

Route::get( 'customers-groups/{id?}', 'Dashboard\CustomersGroupsController@get' );
Route::get( 'customers-groups/{id?}/customers', 'Dashboard\CustomersGroupsController@getCustomers' );
Route::delete( 'customers-groups/{id}', 'Dashboard\CustomersGroupsController@delete' );
Route::post( 'customers-groups', 'Dashboard\CustomersGroupsController@post' );
Route::put( 'customers-groups/{id}', 'Dashboard\CustomersGroupsController@put' );
Route::post( 'customers-groups/transfer-customers', 'Dashboard\CustomersGroupsController@transferOwnership' );