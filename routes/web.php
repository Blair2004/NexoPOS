<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([ 'ns.installed' ])->group( function() {

    Route::get( '/sign-in', 'AuthController@signIn' )->name( 'login' );
    Route::get( '/sign-up', 'AuthController@signUp' );
    Route::get( '/password-lost', 'AuthController@passwordLost' );
    Route::get( '/new-password', 'AuthController@newPassword' );

    Route::post( '/auth/sign-in', 'AuthController@postSignIn' );

    Route::middleware([ 'auth' ])->group( function() {
        Route::get( '/dashboard', 'DashboardController@home' )->name( 'dashboard.index' );
        Route::get( '/dashboard/orders', 'Dashboard\OrdersController@listOrders' );
        Route::get( '/dashboard/customers', 'Dashboard\CustomersController@listCustomers' );
        Route::get( '/dashboard/customers/create', 'Dashboard\CustomersController@createCustomer' );
        Route::get( '/dashboard/customers/groups', 'Dashboard\CustomersController@listCustomersGroups' );
        Route::get( '/dashboard/providers', 'Dashboard\ProvidersController@listProvider' );
        Route::get( '/dashboard/expenses', 'Dashboard\ExpensesController@listExpenses' );
        Route::get( '/dashboard/expenses/categories', 'Dashboard\ExpensesCategoriesController@listExpensesCategories' );
        Route::get( '/dashboard/products', 'Dashboard\ProductsController@listProducts' );
        Route::get( '/dashboard/products/categories', 'Dashboard\CategoryController@listCategories' );
        Route::get( '/dashboard/products/units', 'Dashboard\UnitsController@listUnits' );
        Route::get( '/dashboard/products/units/groups', 'Dashboard\UnitsController@listUnitsGroups' );
        Route::get( '/dashboard/users', 'Dashboard\UsersController@listUsers' );
        Route::get( '/dashboard/profile', 'Dashboard\UsersController@showProfile' );
    });
});

Route::middleware([ 'ns.not-installed' ])->group( function() {
    Route::prefix( '/do-setup/' )->group( function() {
        Route::get( '', 'SetupController@welcome' )->name( 'setup' );
    });
});

Route::get( '/routes', function() {
    return ( array ) app( 'router' )->getRoutes();
});
