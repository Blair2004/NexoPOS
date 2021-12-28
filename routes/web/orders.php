<?php

use App\Classes\Hook;
use App\Http\Controllers\Dashboard\OrdersController;
use Illuminate\Support\Facades\Route;

Route::get( '/orders', [ OrdersController::class, 'listOrders' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.orders' ) );
Route::get( '/orders/instalments', [ OrdersController::class, 'listInstalments' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.orders-instalments' ) );
Route::get( '/orders/payments-types', [ OrdersController::class, 'listPaymentsTypes' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.orders-payments-types' ) );
Route::get( '/orders/payments-types/create', [ OrdersController::class, 'createPaymentType' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.orders-create-types' ) );
Route::get( '/orders/payments-types/edit/{paymentType}', [ OrdersController::class, 'updatePaymentType' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.orders-update-types' ) );
Route::get( '/orders/invoice/{order}', [ OrdersController::class, 'orderInvoice' ]);
Route::get( '/orders/receipt/{order}', [ OrdersController::class, 'orderReceipt' ]);
Route::get( '/orders/refund-receipt/{refund}', [ OrdersController::class, 'orderRefundReceipt' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.orders-refund-receipt' ) );
Route::get( '/orders/payment-receipt/{orderPayment}', [ OrdersController::class, 'getOrderPaymentReceipt' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.orders-payment-receipt' ) );
Route::get( '/pos', [ OrdersController::class, 'showPOS' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.pos' ) );