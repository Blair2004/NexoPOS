<?php

use App\Http\Controllers\Dashboard\OrdersController;
use Illuminate\Support\Facades\Route;

Route::get( 'orders/{id?}', [ OrdersController::class, 'getOrders' ])->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/pos', [ OrdersController::class, 'getPosOrder' ])->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/products', [ OrdersController::class, 'getOrderProducts' ])->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/payments', [ OrdersController::class, 'getOrderPayments' ])->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/refund/{product_id}', [ OrdersController::class, 'refundOrderProduct' ]);
Route::get( 'orders/{id}/full-refund', [ OrdersController::class, 'refundOrderProduct' ]);

Route::post( 'orders/{order}/void', [ OrdersController::class, 'voidOrder' ])->middleware( 'ns.restrict:nexopos.void.orders' );
Route::post( 'orders', [ OrdersController::class, 'create' ]);
Route::post( 'orders/{id}/products', [ OrdersController::class, 'addProductToOrder' ]);

Route::put( 'orders/{id}', [ OrdersController::class, 'updateOrder' ]);

Route::delete( 'orders/{order}', [ OrdersController::class, 'deleteOrder' ])->middleware( 'ns.restrict:nexopos.delete.orders' );
Route::delete( 'orders/{id}/products/{product_id}', [ OrdersController::class, 'deleteOrderProduct' ])->where('id', '[0-9]+');