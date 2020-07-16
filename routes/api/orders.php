<?php
Route::get( 'orders/{id?}', 'Dashboard\OrdersController@listOrders' )->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/products', 'Dashboard\OrdersController@getOrderProducts' )->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/payments', 'Dashboard\OrdersController@getOrderPayments' )->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/refund/{product_id}', 'Dashboard\OrdersController@refundOrderProduct' );
// Route::get( 'orders/{id}/full-refund', 'Dashboard\OrdersController@refundOrderProduct' );
Route::post( 'orders', 'Dashboard\OrdersController@create' );
Route::post( 'orders/{id}/products', 'Dashboard\OrdersController@addProductToOrder' );
Route::delete( 'orders/{id}', 'Dashboard\OrdersController@delete' )->where('id', '[0-9]+');
Route::delete( 'orders/{id}/products/{product_id}', 'Dashboard\OrdersController@deleteOrderProduct' )->where('id', '[0-9]+');