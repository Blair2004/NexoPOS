<?php

use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\CustomersGroupsController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::delete( 'customers/{id}', [ CustomersController::class, 'delete' ] );
Route::delete( 'customers/using-email/{email}', [ CustomersController::class, 'deleteUsingEmail' ] );
Route::get( 'customers/{customer?}', [ CustomersController::class, 'get' ] )->where( [ 'customer' => '[0-9]+' ] );
Route::get( 'customers/recently-active', [ CustomersController::class, 'getRecentlyActive' ] );
Route::get( 'customers/{customer}/orders', [ CustomersController::class, 'getOrders' ] );
Route::get( 'customers/{customer}/addresses', [ CustomersController::class, 'getAddresses' ] );
Route::get( 'customers/{customer}/group', [ CustomersController::class, 'getGroup' ] );
Route::get( 'customers/{customer}/coupons', [ CustomersController::class, 'getCustomerCoupons' ] );
Route::get( 'customers/{customer}/rewards', [ CustomersController::class, 'getCustomerRewards' ] );
Route::get( 'customers/{customer}/account-history', [ CustomersController::class, 'getAccountHistory' ] );
Route::post( 'customers', [ CustomersController::class, 'post' ] );
Route::post( 'customers/search', [ CustomersController::class, 'searchCustomer' ] )->name( ns()->routeName( 'ns-api.customers.search' ) );
Route::post( 'customers/coupons/{coupon}', [ CustomersController::class, 'loadCoupons' ] );
Route::post( 'customers/{customer}/crud/account-history', [ CustomersController::class, 'recordAccountHistory' ] );
Route::put( 'customers/{customer}/crud/{accountHistory}/account-history', [ CustomersController::class, 'updateAccountHistory' ] );
Route::put( 'customers/{customer}', [ CustomersController::class, 'put' ] );

Route::post( 'customers/{customer}/account-history', [ CustomersController::class, 'accountTransaction' ] )
    ->middleware( NsRestrictMiddleware::arguments( 'nexopos.customers.manage-account-history' ) );

Route::get( 'customers-groups/{id?}', [ CustomersGroupsController::class, 'get' ] );
Route::get( 'customers-groups/{id?}/customers', [ CustomersGroupsController::class, 'getCustomers' ] );
Route::delete( 'customers-groups/{id}', [ CustomersGroupsController::class, 'delete' ] );
Route::post( 'customers-groups', [ CustomersGroupsController::class, 'post' ] );
Route::put( 'customers-groups/{id}', [ CustomersGroupsController::class, 'put' ] );
Route::post( 'customers-groups/transfer-customers', [ CustomersGroupsController::class, 'transferOwnership' ] );
