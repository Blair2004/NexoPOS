<?php
Route::delete( 'customers/{id}', 'CustomersController@delete' );
Route::delete( 'customers/using-email/{email}', 'CustomersController@deleteUsingEmail' );
Route::get( 'customers/{customer?}', 'CustomersController@get' )->where([ 'customer' => '[0-9]+' ]);
Route::get( 'customers/{customer}/orders', 'CustomersController@getOrders' );
Route::get( 'customers/{customer}/addresses', 'CustomersController@getAddresses' );
Route::get( 'customers/schema', 'CustomersController@schema' );
Route::post( 'customers', 'CustomersController@post' );
Route::put( 'customers/{customer}', 'CustomersController@put' );

Route::get( 'customers-groups/{id?}', 'CustomersGroupsController@get' );
Route::get( 'customers-groups/{id?}/customers', 'CustomersGroupsController@getCustomers' );
Route::delete( 'customers-groups/{id}', 'CustomersGroupsController@delete' );
Route::post( 'customers-groups', 'CustomersGroupsController@post' );
Route::put( 'customers-groups/{id}', 'CustomersGroupsController@put' );
Route::post( 'customers-groups/transfer-customers', 'CustomersGroupsController@transferOwnership' );