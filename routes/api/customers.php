<?php

use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\CustomersGroupsController;
use Illuminate\Support\Facades\Route;

Route::delete( 'customers/{id}', [ CustomersController::class, 'delete' ]);
Route::delete( 'customers/using-email/{email}', [ CustomersController::class, 'deleteUsingEmail' ]);
Route::get( 'customers/{customer?}', [ CustomersController::class, 'get' ])->where([ 'customer' => '[0-9]+' ]);
Route::get( 'customers/{customer}/orders', [ CustomersController::class, 'getOrders' ]);
Route::get( 'customers/{customer}/addresses', [ CustomersController::class, 'getAddresses' ]);
Route::get( 'customers/schema', [ CustomersController::class, 'schema' ]);
Route::post( 'customers', [ CustomersController::class, 'post' ]);
Route::post( 'customers/search', [ CustomersController::class, 'searchCustomer' ]);
Route::put( 'customers/{customer}', [ CustomersController::class, 'put' ]);

Route::get( 'customers-groups/{id?}', [ CustomersGroupsController::class, 'get' ]);
Route::get( 'customers-groups/{id?}/customers', [ CustomersGroupsController::class, 'getCustomers' ]);
Route::delete( 'customers-groups/{id}', [ CustomersGroupsController::class, 'delete' ]);
Route::post( 'customers-groups', [ CustomersGroupsController::class, 'post' ]);
Route::put( 'customers-groups/{id}', [ CustomersGroupsController::class, 'put' ]);
Route::post( 'customers-groups/transfer-customers', [ CustomersGroupsController::class, 'transferOwnership' ]);