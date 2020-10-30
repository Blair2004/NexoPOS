<?php

use App\Http\Controllers\Dashboard\OrdersController;
use App\Http\Controllers\DashboardController;
use App\Http\Middleware\CheckMigrationStatus;
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

Route::middleware([ 'ns.installed', CheckMigrationStatus::class ])->group( function() {
    Route::get( '/sign-in', 'AuthController@signIn' )->name( 'ns.login' );
    Route::get( '/sign-up', 'AuthController@signUp' )->name( 'ns.register' );
    Route::get( '/password-lost', 'AuthController@passwordLost' );
    Route::get( '/new-password', 'AuthController@newPassword' );

    Route::post( '/auth/sign-in', 'AuthController@postSignIn' );
    Route::post( '/auth/sign-up', 'AuthController@postSignUp' )->name( 'ns.register.post' );
    Route::get( '/sign-out', 'AuthController@signOut' )->name( 'ns.logout' );
    Route::get( '/database-update/', 'UpdateController@updateDatabase' )->withoutMiddleware([ CheckMigrationStatus::class ])
        ->name( 'ns.database-update' );

    Route::middleware([ 
        'auth',
        'ns.check-application-health',
    ])->group( function() {
        Route::get( '/dashboard', 'DashboardController@home' )->name( 'ns.dashboard.home' );

        Route::get( '/dashboard/orders', [ OrdersController::class, 'listOrders' ]);
        Route::get( '/dashboard/orders/invoice/{order}', [ OrdersController::class, 'orderInvoice' ]);
        Route::get( '/dashboard/orders/receipt/{order}', [ OrdersController::class, 'orderReceipt' ]);
        Route::get( '/dashboard/pos', [ OrdersController::class, 'showPOS' ]);

        Route::get( '/dashboard/customers', 'Dashboard\CustomersController@listCustomers' );
        Route::get( '/dashboard/customers/create', 'Dashboard\CustomersController@createCustomer' );
        Route::get( '/dashboard/customers/edit/{customer}', 'Dashboard\CustomersController@editCustomer' );
        Route::get( '/dashboard/customers/groups', 'Dashboard\CustomersGroupsController@listCustomersGroups' );
        Route::get( '/dashboard/customers/groups/create', 'Dashboard\CustomersGroupsController@createCustomerGroup' );
        Route::get( '/dashboard/customers/groups/edit/{group}', 'Dashboard\CustomersGroupsController@editCustomerGroup' );
        
        Route::get( '/dashboard/customers/rewards-system', 'Dashboard\RewardsSystemController@list' );
        Route::get( '/dashboard/customers/rewards-system/create', 'Dashboard\RewardsSystemController@create' );
        Route::get( '/dashboard/customers/rewards-system/edit/{reward}', 'Dashboard\RewardsSystemController@edit' );

        Route::get( '/dashboard/customers/coupons', 'Dashboard\CustomersController@listCoupons' );
        Route::get( '/dashboard/customers/coupons/create', 'Dashboard\CustomersController@createCoupon' );
        Route::get( '/dashboard/customers/coupons/edit/{coupon}', 'Dashboard\CustomersController@editCoupon' );

        Route::get( '/dashboard/modules', 'Dashboard\ModulesController@listModules' )->name( 'ns.dashboard.modules.list' );
        Route::get( '/dashboard/modules/upload', 'Dashboard\ModulesController@showUploadModule' )->name( 'ns.dashboard.modules.upload' );
        Route::get( '/dashboard/modules/download/{identifier}', 'Dashboard\ModulesController@downloadModule' )->name( 'ns.dashboard.modules.upload' );
        Route::get( '/dashboard/modules/migrate/{namespace}', 'Dashboard\ModulesController@migrateModule' )->name( 'ns.dashboard.modules.migrate' );

        Route::get( '/dashboard/procurements', 'Dashboard\ProcurementController@listProcurements' );
        Route::get( '/dashboard/procurements/create', 'Dashboard\ProcurementController@createProcurement' );
        Route::get( '/dashboard/procurements/edit/{procurement}', 'Dashboard\ProcurementController@updateProcurement' );

        Route::get( '/dashboard/medias', 'Dashboard\MediasController@showMedia' );

        Route::get( '/dashboard/providers', 'Dashboard\ProvidersController@listProviders' );
        Route::get( '/dashboard/providers/create', 'Dashboard\ProvidersController@createProvider' );
        Route::get( '/dashboard/providers/edit/{provider}', 'Dashboard\ProvidersController@editProvider' );

        Route::get( '/dashboard/expenses', 'Dashboard\ExpensesController@listExpenses' );
        Route::get( '/dashboard/expenses/create', 'Dashboard\ExpensesController@createExpense' );
        Route::get( '/dashboard/expenses/edit/{expense}', 'Dashboard\ExpensesController@editExpense' );
        Route::get( '/dashboard/expenses/history', 'Dashboard\ExpensesController@expensesHistory' );
        
        Route::get( '/dashboard/expenses/categories', 'Dashboard\ExpensesCategoriesController@listExpensesCategories' );
        Route::get( '/dashboard/expenses/categories/create', 'Dashboard\ExpensesCategoriesController@createExpenseCategory' );
        Route::get( '/dashboard/expenses/categories/edit/{category}', 'Dashboard\ExpensesCategoriesController@editExpenseCategory' );

        Route::get( '/dashboard/products', 'Dashboard\ProductsController@listProducts' );
        Route::get( '/dashboard/products/create', 'Dashboard\ProductsController@createProduct' );
        Route::get( '/dashboard/products/stock-adjustment', 'Dashboard\ProductsController@showStockAdjustment' );
        Route::get( '/dashboard/products/edit/{product}', 'Dashboard\ProductsController@editProduct' );
        Route::get( '/dashboard/products/{product}/units', 'Dashboard\ProductsController@productUnits' );
        Route::get( '/dashboard/products/{product}/history', 'Dashboard\ProductsController@productHistory' );
        Route::get( '/dashboard/products/categories', 'Dashboard\CategoryController@listCategories' );
        Route::get( '/dashboard/products/categories/create', 'Dashboard\CategoryController@createCategory' );
        Route::get( '/dashboard/products/categories/edit/{category}', 'Dashboard\CategoryController@editCategory' );

        Route::get( '/dashboard/taxes', 'Dashboard\TaxesController@listTaxes' );
        Route::get( '/dashboard/taxes/create', 'Dashboard\TaxesController@createTax' );
        Route::get( '/dashboard/taxes/edit/{tax}', 'Dashboard\TaxesController@editTax' );
        Route::get( '/dashboard/taxes/groups', 'Dashboard\TaxesController@taxesGroups' );
        Route::get( '/dashboard/taxes/groups/create', 'Dashboard\TaxesController@createTaxGroups' );
        Route::get( '/dashboard/taxes/groups/edit/{group}', 'Dashboard\TaxesController@editTaxGroup' );
        
        Route::get( '/dashboard/units', 'Dashboard\UnitsController@listUnits' );
        Route::get( '/dashboard/units/edit/{unit}', 'Dashboard\UnitsController@editUnit' );
        Route::get( '/dashboard/units/create', 'Dashboard\UnitsController@createUnit' );
        Route::get( '/dashboard/units/groups', 'Dashboard\UnitsController@listUnitsGroups' );
        Route::get( '/dashboard/units/groups/create', 'Dashboard\UnitsController@createUnitGroup' );
        Route::get( '/dashboard/units/groups/edit/{group}', 'Dashboard\UnitsController@editUnitGroup' );
        
        Route::get( '/dashboard/users', 'Dashboard\UsersController@listUsers' );
        Route::get( '/dashboard/users/create', 'Dashboard\UsersController@createUser' );
        Route::get( '/dashboard/users/edit/{user}', 'Dashboard\UsersController@editUser' );
        Route::get( '/dashboard/users/roles/permissions-manager', 'Dashboard\UsersController@permissionManager' );
        Route::get( '/dashboard/users/profile', 'Dashboard\UsersController@getProfile' )->name( 'ns.dashboard.users.profile' );
        Route::get( '/dashboard/users/roles', 'Dashboard\UsersController@rolesList' );
        Route::get( '/dashboard/users/roles/{id}', 'Dashboard\UsersController@editRole' );

        Route::get( '/dashboard/settings/{settings}', 'Dashboard\SettingsController@getSettings' );
        Route::get( '/dashboard/settings/form/{settings}', 'Dashboard\SettingsController@loadSettingsForm' );

        Route::get( '/dashboard/experiments', 'DashboardController@experiments' );
    });

    include_once( dirname( __FILE__ ) . '/api/stores.php' );
});

Route::middleware([ 'ns.not-installed' ])->group( function() {
    Route::prefix( '/do-setup/' )->group( function() {
        Route::get( '', 'SetupController@welcome' )->name( 'setup' );
    });
});

Route::get( '/routes', function() {
    return ( array ) app( 'router' )->getRoutes();
});

Route::prefix( 'nexopos/v4/hello', fn() => 'Hello World' );
