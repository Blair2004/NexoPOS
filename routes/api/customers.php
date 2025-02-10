<?php

use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\CustomersGroupsController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::delete( 'customers/{id}', [ CustomersController::class, 'delete' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.delete.customers' ) );
Route::delete( 'customers/using-email/{email}', [ CustomersController::class, 'deleteUsingEmail' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.delete.customers' ) );

Route::middleware( NsRestrictMiddleware::arguments( 'nexopos.read.customers' ) )->group( function () {
    Route::get( 'customers/{customer?}', [ CustomersController::class, 'get' ] )->where( [ 'customer' => '[0-9]+' ] );
    Route::get( 'customers/recently-active', [ CustomersController::class, 'getRecentlyActive' ] );
    Route::get( 'customers/{customer}/orders', [ CustomersController::class, 'getOrders' ] );
    Route::get( 'customers/{customer}/addresses', [ CustomersController::class, 'getAddresses' ] );
    Route::get( 'customers/{customer}/group', [ CustomersController::class, 'getGroup' ] );
    Route::get( 'customers/{customer}/coupons', [ CustomersController::class, 'getCustomerCoupons' ] );
    Route::get( 'customers/{customer}/rewards', [ CustomersController::class, 'getCustomerRewards' ] );
    Route::get( 'customers/{customer}/account-history', [ CustomersController::class, 'getAccountHistory' ] );
} );

Route::post( 'customers', [ CustomersController::class, 'post' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.create.customers' ) );
Route::post( 'customers/search', [ CustomersController::class, 'searchCustomer' ] )->name( ns()->routeName( 'ns-api.customers.search' ) )->middleware( NsRestrictMiddleware::arguments( 'nexopos.read.customers' ) );
Route::post( 'customers/coupons/{coupon}', [ CustomersController::class, 'loadCoupons' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.read.customers' ) );
Route::post( 'customers/{customer}/crud/account-history', [ CustomersController::class, 'recordAccountHistory' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.customers.manage-account-history' ) );
Route::put( 'customers/{customer}', [ CustomersController::class, 'put' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.update.customers' ) );

Route::post( 'customers/{customer}/account-history', [ CustomersController::class, 'accountTransaction' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.customers.manage-account-history' ) );

Route::get( 'customers-groups/{id?}', [ CustomersGroupsController::class, 'get' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.read.customers-groups' ) );
Route::get( 'customers-groups/{id?}/customers', [ CustomersGroupsController::class, 'getCustomers' ] )->middleware( NsRestrictMiddleware::arguments( [
    'nexopos.read.customers-groups', 'nexopos.read.customers',
] ) );
Route::delete( 'customers-groups/{id}', [ CustomersGroupsController::class, 'delete' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.delete.customers-groups' ) );
Route::post( 'customers-groups', [ CustomersGroupsController::class, 'post' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.create.customers-groups' ) );
Route::put( 'customers-groups/{id}', [ CustomersGroupsController::class, 'put' ] )->middleware( NsRestrictMiddleware::arguments( 'nexopos.update.customers-groups' ) );
Route::post( 'customers-groups/transfer-customers', [ CustomersGroupsController::class, 'transferOwnership' ] )->middleware( NsRestrictMiddleware::arguments( [
    'nexopos.update.customers-groups', 'nexopos.update.customers',
] ) );
