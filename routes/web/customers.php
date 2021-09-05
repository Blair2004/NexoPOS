<?php

use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\CustomersGroupsController;
use App\Http\Controllers\Dashboard\RewardsSystemController;
use Illuminate\Support\Facades\Route;

Route::get( '/customers', [ CustomersController::class, 'listCustomers' ]);
Route::get( '/customers/create', [ CustomersController::class, 'createCustomer' ]);
Route::get( '/customers/edit/{customer}', [ CustomersController::class, 'editCustomer' ]);
Route::get( '/customers/{customer}/rewards', [ CustomersController::class, 'getCustomersRewards' ])->name( ns()->routeName( 'ns.dashboard.customers-rewards' ) );
Route::get( '/customers/{customer}/rewards/edit/{reward}', [ CustomersController::class, 'editCustomerReward' ]);
Route::get( '/customers/{customer}/orders', [ CustomersController::class, 'getCustomersOrders' ]);
Route::get( '/customers/{customer}/coupons', [ CustomersController::class, 'getCustomersCoupons' ]);
Route::get( '/customers/{customer}/account-history', [ CustomersController::class, 'getCustomerAccountHistory' ]);
Route::get( '/customers/{customer}/account-history/create', [ CustomersController::class, 'createCustomerAccountHistory' ]);
Route::get( '/customers/{customer}/account-history/edit/{customerAccountHistory}', [ CustomersController::class, 'editCustomerAccountHistory' ]);
Route::get( '/customers/groups', [ CustomersGroupsController::class, 'listCustomersGroups' ]);
Route::get( '/customers/groups/create', [ CustomersGroupsController::class, 'createCustomerGroup' ]);
Route::get( '/customers/groups/edit/{group}', [ CustomersGroupsController::class, 'editCustomerGroup' ]);
Route::get( '/customers/rewards-system', [ RewardsSystemController::class, 'list' ]);
Route::get( '/customers/rewards-system/create', [ RewardsSystemController::class, 'create' ]);
Route::get( '/customers/rewards-system/edit/{reward}', [ RewardsSystemController::class, 'edit' ])->name( ns()->routeName( 'ns.dashboard.rewards-edit' ) );
Route::get( '/customers/coupons', [ CustomersController::class, 'listCoupons' ]);
Route::get( '/customers/coupons/create', [ CustomersController::class, 'createCoupon' ]);
Route::get( '/customers/coupons/edit/{coupon}', [ CustomersController::class, 'editCoupon' ]);