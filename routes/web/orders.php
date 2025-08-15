<?php

use App\Http\Controllers\Dashboard\OrdersController;
use Illuminate\Support\Facades\Route;

Route::get( '/orders', [ OrdersController::class, 'listOrders' ] )->name( ns()->routeName( 'ns.dashboard.orders' ) );
Route::get( '/orders/instalments', [ OrdersController::class, 'listInstalments' ] )->name( ns()->routeName( 'ns.dashboard.orders-instalments' ) );
Route::get( '/orders/payments-types', [ OrdersController::class, 'listPaymentsTypes' ] )->name( ns()->routeName( 'ns.dashboard.orders-payments-types' ) );
Route::get( '/orders/payments-types/create', [ OrdersController::class, 'createPaymentType' ] )->name( ns()->routeName( 'ns.dashboard.orders-create-types' ) );
Route::get( '/orders/payments-types/edit/{paymentType}', [ OrdersController::class, 'updatePaymentType' ] )->name( ns()->routeName( 'ns.dashboard.orders-update-types' ) );
Route::get( '/orders/invoice/{order}', [ OrdersController::class, 'orderInvoice' ] )->name( ns()->routeName( 'ns.dashboard.orders-invoice' ) );
Route::get( '/orders/receipt/{order}', [ OrdersController::class, 'orderReceipt' ] )->name( ns()->routeName( 'ns.dashboard.orders-receipt' ) );
Route::get( '/orders/refund-receipt/{refund}', [ OrdersController::class, 'orderRefundReceipt' ] )->name( ns()->routeName( 'ns.dashboard.orders-refund-receipt' ) );
Route::get( '/orders/payment-receipt/{orderPayment}', [ OrdersController::class, 'getOrderPaymentReceipt' ] )->name( ns()->routeName( 'ns.dashboard.orders-payment-receipt' ) );
Route::get( '/pos', [ OrdersController::class, 'showPOS' ] )->name( ns()->routeName( 'ns.dashboard.pos' ) );
