<?php

use App\Http\Controllers\Dashboard\OrdersController;
use Illuminate\Support\Facades\Route;

Route::get( 'orders/{id?}', [ OrdersController::class, 'getOrders' ])->where( 'id', '[0-9]+');
Route::get( 'orders/payments', [ OrdersController::class, 'getSupportedPayments' ]);
Route::get( 'orders/{id}/pos', [ OrdersController::class, 'getPosOrder' ])->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/products', [ OrdersController::class, 'getOrderProducts' ])->where( 'id', '[0-9]+');
Route::get( 'orders/{order}/products/refunded', [ OrdersController::class, 'getOrderProductsRefunded' ])->where( 'id', '[0-9]+');
Route::get( 'orders/{order}/refunds', [ OrdersController::class, 'getOrderRefunds' ])->where( 'id', '[0-9]+');
Route::get( 'orders/{id}/payments', [ OrdersController::class, 'getOrderPayments' ])->where( 'id', '[0-9]+');
Route::get( 'orders/{order}/instalments', [ OrdersController::class, 'getOrderInstalments' ])->where( 'id', '[0-9]+')->middleware( 'ns.restrict:nexopos.read.orders-instalments' );
Route::get( 'orders/{order}/print/{doc?}', [ OrdersController::class, 'printOrder' ])->where( 'id', '[0-9]+');

/**
 * @deprecated
 */
Route::get( 'orders/{id}/refund/{product_id}', [ OrdersController::class, 'refundOrderProduct' ]);
Route::get( 'orders/{id}/full-refund', [ OrdersController::class, 'refundOrderProduct' ]);

Route::post( 'orders/{order}/instalments/{instalment}/pay', [ OrdersController::class, 'payInstalment' ])->where( 'id', '[0-9]+')->middleware( 'ns.restrict:nexopos.update.orders-instalments' );
Route::post( 'orders/{order}/void', [ OrdersController::class, 'voidOrder' ])->middleware( 'ns.restrict:nexopos.void.orders' );
Route::post( 'orders', [ OrdersController::class, 'create' ]);
Route::post( 'orders/{id}/products', [ OrdersController::class, 'addProductToOrder' ]);
Route::post( 'orders/{order}/processing', [ OrdersController::class, 'changeOrderProcessingStatus' ])->middleware( 'ns.restrict:nexopos.update.orders' );
Route::post( 'orders/{order}/delivery', [ OrdersController::class, 'changeOrderDeliveryStatus' ])->middleware( 'ns.restrict:nexopos.update.orders' );
Route::post( 'orders/{order}/payments', [ OrdersController::class, 'addPayment' ])->middleware( 'ns.restrict:nexopos.make-payment.orders' );
Route::post( 'orders/{order}/refund', [ OrdersController::class, 'makeOrderRefund' ])
->middleware( 'ns.restrict:nexopos.refund.orders' );
Route::post( 'orders/{order}/instalments', [ OrdersController::class, 'createInstalment' ])->middleware( 'ns.restrict:nexopos.create.orders-instalments' );

Route::put( 'orders/{order}/instalments/{instalment}', [ OrdersController::class, 'updateInstalment' ])->middleware( 'ns.restrict:nexopos.update.orders-instalments' );
Route::put( 'orders/{id}', [ OrdersController::class, 'updateOrder' ])->middleware( 'ns.restrict:nexopos.update.orders' );

Route::delete( 'orders/{order}/instalments/{instalment}', [ OrdersController::class, 'deleteInstalment' ])
    ->middleware( 'ns.restrict:nexopos.delete.orders-instalments' );
Route::delete( 'orders/{order}', [ OrdersController::class, 'deleteOrder' ])->middleware( 'ns.restrict:nexopos.delete.orders' );
Route::delete( 'orders/{id}/products/{product_id}', [ OrdersController::class, 'deleteOrderProduct' ])->where('id', '[0-9]+');