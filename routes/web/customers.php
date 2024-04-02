<?php

use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\CustomersGroupsController;
use App\Http\Controllers\Dashboard\RewardsSystemController;
use Illuminate\Support\Facades\Route;

Route::get( '/customers', [ CustomersController::class, 'listCustomers' ] )->name( ns()->routeName( 'ns.dashboard.customers-list' ) ); // @todo update
Route::get( '/customers/create', [ CustomersController::class, 'createCustomer' ] )->name( ns()->routeName( 'ns.dashboard.customers-create' ) );
Route::get( '/customers/edit/{customer}', [ CustomersController::class, 'editCustomer' ] )->name( ns()->routeName( 'ns.dashboard.customers-edit' ) );
Route::get( '/customers/{customer}/rewards', [ CustomersController::class, 'getCustomersRewards' ] )->name( ns()->routeName( 'ns.dashboard.customers-rewards-list' ) ); // @todo update
Route::get( '/customers/{customer}/rewards/edit/{reward}', [ CustomersController::class, 'editCustomerReward' ] )->name( ns()->routeName( 'ns.dashboard.customers-rewards-edit' ) );
Route::get( '/customers/{customer}/orders', [ CustomersController::class, 'getCustomersOrders' ] )->name( ns()->routeName( 'ns.dashboard.customers-orders-list' ) ); // @todo update
Route::get( '/customers/{customer}/coupons', [ CustomersController::class, 'getCustomersCoupons' ] )->name( ns()->routeName( 'ns.dashboard.customers-coupons-list' ) );
Route::get( '/customers/{customer}/coupons/{customerCoupon}/history', [ CustomersController::class, 'listCustomerCouponHistory' ] )->name( ns()->routeName( 'ns.dashboard.customers-coupons-history-list' ) );
Route::get( '/customers/{customer}/account-history', [ CustomersController::class, 'getCustomerAccountHistory' ] )->name( ns()->routeName( 'ns.dashboard.customers-account-history-list' ) ); // @todo update
Route::get( '/customers/{customer}/account-history/create', [ CustomersController::class, 'createCustomerAccountHistory' ] )->name( ns()->routeName( 'ns.dashboard.customers-account-history-create' ) );
Route::get( '/customers/{customer}/account-history/edit/{customerAccountHistory}', [ CustomersController::class, 'editCustomerAccountHistory' ] )->name( ns()->routeName( 'ns.dashboard.customers-account-history-edit' ) );
Route::get( '/customers/groups', [ CustomersGroupsController::class, 'listCustomersGroups' ] )->name( ns()->routeName( 'ns.dashboard.customersgroups-list' ) ); // @todo update
Route::get( '/customers/groups/create', [ CustomersGroupsController::class, 'createCustomerGroup' ] )->name( ns()->routeName( 'ns.dashboard.customersgroups-create' ) );
Route::get( '/customers/groups/edit/{group}', [ CustomersGroupsController::class, 'editCustomerGroup' ] )->name( ns()->routeName( 'ns.dashboard.customersgroups-edit' ) );
Route::get( '/customers/rewards-system', [ RewardsSystemController::class, 'list' ] )->name( ns()->routeName( 'ns.dashboard.rewards-list' ) ); // @todo update
Route::get( '/customers/rewards-system/create', [ RewardsSystemController::class, 'create' ] )->name( ns()->routeName( 'ns.dashboard.rewards-create' ) );
Route::get( '/customers/rewards-system/edit/{reward}', [ RewardsSystemController::class, 'edit' ] )->name( ns()->routeName( 'ns.dashboard.rewards-edit' ) );
Route::get( '/customers/coupons', [ CustomersController::class, 'listCoupons' ] )->name( ns()->routeName( 'ns.dashboard.all-customers-coupons-list' ) ); // @todo update
Route::get( '/customers/coupons/create', [ CustomersController::class, 'createCoupon' ] )->name( ns()->routeName( 'ns.dashboard.customers-coupons-create' ) );
Route::get( '/customers/coupons/edit/{coupon}', [ CustomersController::class, 'editCoupon' ] )->name( ns()->routeName( 'ns.dashboard.customers-coupons-edit' ) );
Route::get( '/customers/coupons/history/{coupon}', [ CustomersController::class, 'couponHistory' ] )->name( ns()->routeName( 'ns.dashboard.customers-coupons-history' ) );
Route::get( '/customers/coupons-generated', [ CustomersController::class, 'listGeneratedCoupons' ] )->name( ns()->routeName( 'ns.dashboard.customers-coupons-generated-list' ) ); // @todo update
Route::get( '/customers/coupons-generated/edit/{coupon}', [ CustomersController::class, 'editGeneratedCoupon' ] )->name( ns()->routeName( 'ns.dashboard.customers-coupons-generated-edit' ) );
