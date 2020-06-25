<?php
Route::get( 'orders/{id?}', 'OrdersController@listOrders' )->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/products', 'OrdersController@getOrderProducts' )->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/payments', 'OrdersController@getOrderPayments' )->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/refund/{product_id}', 'OrdersController@refundOrderProduct' );
// Route::get( 'orders/{id}/full-refund', 'OrdersController@refundOrderProduct' );
Route::post( 'orders', 'OrdersController@create' );
Route::post( 'orders/{id}/products', 'OrdersController@addProductToOrder' );
Route::delete( 'orders/{id}', 'OrdersController@delete' )->where('id', '[0-9]+');
Route::delete( 'orders/{id}/products/{product_id}', 'OrdersController@deleteOrderProduct' )->where('id', '[0-9]+');