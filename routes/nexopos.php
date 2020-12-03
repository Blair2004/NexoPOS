<?php

use App\Classes\Hook;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\CustomersGroupsController;
use App\Http\Controllers\Dashboard\ExpensesCategoriesController;
use App\Http\Controllers\Dashboard\ExpensesController;
use App\Http\Controllers\Dashboard\MediasController;
use App\Http\Controllers\Dashboard\ProductsController;
use App\Http\Controllers\Dashboard\OrdersController;
use App\Http\Controllers\Dashboard\ProcurementController;
use App\Http\Controllers\Dashboard\ProvidersController;
use App\Http\Controllers\Dashboard\RewardsSystemController;
use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Controllers\Dashboard\TaxesController;
use App\Http\Controllers\Dashboard\UnitsController;
use App\Http\Controllers\Dashboard\UsersController;
use App\Http\Controllers\Dashboard\ReportsController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get( '', [ DashboardController::class, 'home' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.home' ) );
    
Route::get( '/orders', [ OrdersController::class, 'listOrders' ]);
Route::get( '/orders/invoice/{order}', [ OrdersController::class, 'orderInvoice' ]);
Route::get( '/orders/receipt/{order}', [ OrdersController::class, 'orderReceipt' ]);
Route::get( '/pos', [ OrdersController::class, 'showPOS' ]);

Route::get( '/customers', [ CustomersController::class, 'listCustomers' ]);
Route::get( '/customers/create', [ CustomersController::class, 'createCustomer' ]);
Route::get( '/customers/edit/{customer}', [ CustomersController::class, 'editCustomer' ]);
Route::get( '/customers/{customer}/orders', [ CustomersController::class, 'getCustomersOrders' ]);
Route::get( '/customers/groups', [ CustomersGroupsController::class, 'listCustomersGroups' ]);
Route::get( '/customers/groups/create', [ CustomersGroupsController::class, 'createCustomerGroup' ]);
Route::get( '/customers/groups/edit/{group}', [ CustomersGroupsController::class, 'editCustomerGroup' ]);
Route::get( '/customers/rewards-system', [ RewardsSystemController::class, 'list' ]);
Route::get( '/customers/rewards-system/create', [ RewardsSystemController::class, 'create' ]);
Route::get( '/customers/rewards-system/edit/{reward}', [ RewardsSystemController::class, 'edit' ]);
Route::get( '/customers/coupons', [ CustomersController::class, 'listCoupons' ]);
Route::get( '/customers/coupons/create', [ CustomersController::class, 'createCoupon' ]);
Route::get( '/customers/coupons/edit/{coupon}', [ CustomersController::class, 'editCoupon' ]);

Route::get( '/procurements', [ ProcurementController::class, 'listProcurements' ]);
Route::get( '/procurements/create', [ ProcurementController::class, 'createProcurement' ]);
Route::get( '/procurements/edit/{procurement}', [ ProcurementController::class, 'updateProcurement' ]);
Route::get( '/procurements/edit/{procurement}/invoice', [ ProcurementController::class, 'procurementInvoice' ]);

Route::get( '/medias', [ MediasController::class, 'showMedia' ]);

Route::get( '/providers', [ ProvidersController::class, 'listProviders' ]);
Route::get( '/providers/create', [ ProvidersController::class, 'createProvider' ]);
Route::get( '/providers/edit/{provider}', [ ProvidersController::class, 'editProvider' ]);

Route::get( '/expenses', [ ExpensesController::class, 'listExpenses' ]);
Route::get( '/expenses/create', [ ExpensesController::class, 'createExpense' ]);
Route::get( '/expenses/edit/{expense}', [ ExpensesController::class, 'editExpense' ]);
Route::get( '/expenses/history', [ ExpensesController::class, 'expensesHistory' ]);

Route::get( '/expenses/categories', [ ExpensesCategoriesController::class, 'listExpensesCategories' ]);
Route::get( '/expenses/categories/create', [ ExpensesCategoriesController::class, 'createExpenseCategory' ]);
Route::get( '/expenses/categories/edit/{category}', [ ExpensesCategoriesController::class, 'editExpenseCategory' ]);

Route::get( '/products', [ ProductsController::class, 'listProducts' ]);
Route::get( '/products/create', [ ProductsController::class, 'createProduct' ]);
Route::get( '/products/stock-adjustment', [ ProductsController::class, 'showStockAdjustment' ]);
Route::get( '/products/edit/{product}', [ ProductsController::class, 'editProduct' ]);
Route::get( '/products/{product}/units', [ ProductsController::class, 'productUnits' ]);
Route::get( '/products/{product}/history', [ ProductsController::class, 'productHistory' ]);
Route::get( '/products/categories', [ CategoryController::class, 'listCategories' ]);
Route::get( '/products/categories/create', [ CategoryController::class, 'createCategory' ]);
Route::get( '/products/categories/edit/{category}', [ CategoryController::class, 'editCategory' ]);

Route::get( '/taxes', [ TaxesController::class, 'listTaxes' ]);
Route::get( '/taxes/create', [ TaxesController::class, 'createTax' ]);
Route::get( '/taxes/edit/{tax}', [ TaxesController::class, 'editTax' ]);
Route::get( '/taxes/groups', [ TaxesController::class, 'taxesGroups' ]);
Route::get( '/taxes/groups/create', [ TaxesController::class, 'createTaxGroups' ]);
Route::get( '/taxes/groups/edit/{group}', [ TaxesController::class, 'editTaxGroup' ]);

Route::get( '/units', [ UnitsController::class, 'listUnits' ]);
Route::get( '/units/edit/{unit}', [ UnitsController::class, 'editUnit' ]);
Route::get( '/units/create', [ UnitsController::class, 'createUnit' ]);
Route::get( '/units/groups', [ UnitsController::class, 'listUnitsGroups' ]);
Route::get( '/units/groups/create', [ UnitsController::class, 'createUnitGroup' ]);
Route::get( '/units/groups/edit/{group}', [ UnitsController::class, 'editUnitGroup' ]);

Route::get( '/reports/sales', [ ReportsController::class, 'salesReport' ]);
Route::get( '/reports/sold-stock', [ ReportsController::class, 'soldStock' ]);
Route::get( '/reports/profit', [ ReportsController::class, 'profit' ]);

Route::get( '/settings/{settings}', [ SettingsController::class, 'getSettings' ]);
Route::get( '/settings/form/{settings}', [ SettingsController::class, 'loadSettingsForm' ]);