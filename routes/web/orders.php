<?php

use App\Http\Controllers\Dashboard\KitchenController;
use App\Http\Controllers\Dashboard\OrdersController;
use App\Http\Middleware\NsRestrictAnyMiddleware;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::get( '/orders', [ OrdersController::class, 'listOrders' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.read.orders' ) )->name( ns()->routeName( 'ns.dashboard.orders' ) );
Route::get( '/orders/instalments', [ OrdersController::class, 'listInstalments' ] )->name( ns()->routeName( 'ns.dashboard.orders-instalments' ) );
Route::get( '/orders/payments-types', [ OrdersController::class, 'listPaymentsTypes' ] )->name( ns()->routeName( 'ns.dashboard.orders-payments-types' ) );
Route::get( '/orders/payments-types/create', [ OrdersController::class, 'createPaymentType' ] )->name( ns()->routeName( 'ns.dashboard.orders-create-types' ) );
Route::get( '/orders/payments-types/edit/{paymentType}', [ OrdersController::class, 'updatePaymentType' ] )->name( ns()->routeName( 'ns.dashboard.orders-update-types' ) );
$orderReadMiddleware = NsRestrictAnyMiddleware::class . ':nexopos.read.orders,nexopos.pos.read-all-orders,nexopos.pos.read-own-orders';
Route::get( '/orders/invoice/{order}', [ OrdersController::class, 'orderInvoice' ] )->middleware( $orderReadMiddleware )->name( ns()->routeName( 'ns.dashboard.orders-invoice' ) );
Route::get( '/orders/receipt/{order}', [ OrdersController::class, 'orderReceipt' ] )->middleware( $orderReadMiddleware )->name( ns()->routeName( 'ns.dashboard.orders-receipt' ) );
Route::get( '/orders/refund-receipt/{refund}', [ OrdersController::class, 'orderRefundReceipt' ] )->middleware( $orderReadMiddleware )->name( ns()->routeName( 'ns.dashboard.orders-refund-receipt' ) );
Route::get( '/orders/payment-receipt/{orderPayment}', [ OrdersController::class, 'getOrderPaymentReceipt' ] )->middleware( $orderReadMiddleware )->name( ns()->routeName( 'ns.dashboard.orders-payment-receipt' ) );
Route::get( '/pos', [ OrdersController::class, 'showPOS' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.pos.access' ) )->name( ns()->routeName( 'ns.dashboard.pos' ) );

/**
 * Kitchen Display System (KDS) — MVP.
 * The screen polls the kitchen API; the ticket is a printable
 * browser page (autoprint opens the system print dialog).
 */
Route::get( '/kitchen', [ KitchenController::class, 'showKitchen' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.read.orders' ) )->name( ns()->routeName( 'ns.dashboard.kitchen' ) );
Route::get( '/orders/{order}/kitchen-ticket/{zone?}', [ KitchenController::class, 'kitchenTicket' ] )->where( 'zone', '[0-9]+' )->middleware( $orderReadMiddleware )->name( ns()->routeName( 'ns.dashboard.orders-kitchen-ticket' ) );
