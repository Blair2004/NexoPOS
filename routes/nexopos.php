<?php
use App\Http\Controllers\Dashboard\CustomersController;
use App\Http\Controllers\Dashboard\CustomersGroupsController;
use App\Http\Controllers\Dashboard\ProductsController;
use App\Http\Controllers\Dashboard\OrdersController;

Route::get( '', 'DashboardController@home' )->name( 'ns.dashboard.home' );
    
Route::get( '/orders', [ OrdersController::class, 'listOrders' ]);
Route::get( '/orders/invoice/{order}', [ OrdersController::class, 'orderInvoice' ]);
Route::get( '/orders/receipt/{order}', [ OrdersController::class, 'orderReceipt' ]);
Route::get( '/pos', [ OrdersController::class, 'showPOS' ]);

Route::get( '/customers', [ CustomersController::class, 'listCustomers' ]);
Route::get( '/customers/create', [ CustomersController::class, 'createCustomer' ]);
Route::get( '/customers/edit/{customer}', [ CustomersController::class, 'editCustomer' ]);
Route::get( '/customers/groups', [ CustomersGroupsController::class, 'listCustomersGroups' ]);
Route::get( '/customers/groups/create', [ CustomersGroupsController::class, 'createCustomerGroup' ]);
Route::get( '/customers/groups/edit/{group}', [ CustomersGroupsController::class, 'editCustomerGroup' ]);

Route::get( '/customers/rewards-system', 'Dashboard\RewardsSystemController@list' );
Route::get( '/customers/rewards-system/create', 'Dashboard\RewardsSystemController@create' );
Route::get( '/customers/rewards-system/edit/{reward}', 'Dashboard\RewardsSystemController@edit' );

Route::get( '/customers/coupons', [ CustomersController::class, 'listCoupons' ]);
Route::get( '/customers/coupons/create', [ CustomersController::class, 'createCoupon' ]);
Route::get( '/customers/coupons/edit/{coupon}', [ CustomersController::class, 'editCoupon' ]);
Route::get( '/procurements', 'Dashboard\ProcurementController@listProcurements' );
Route::get( '/procurements/create', 'Dashboard\ProcurementController@createProcurement' );
Route::get( '/procurements/edit/{procurement}', 'Dashboard\ProcurementController@updateProcurement' );

Route::get( '/medias', 'Dashboard\MediasController@showMedia' );

Route::get( '/providers', 'Dashboard\ProvidersController@listProviders' );
Route::get( '/providers/create', 'Dashboard\ProvidersController@createProvider' );
Route::get( '/providers/edit/{provider}', 'Dashboard\ProvidersController@editProvider' );

Route::get( '/expenses', 'Dashboard\ExpensesController@listExpenses' );
Route::get( '/expenses/create', 'Dashboard\ExpensesController@createExpense' );
Route::get( '/expenses/edit/{expense}', 'Dashboard\ExpensesController@editExpense' );
Route::get( '/expenses/history', 'Dashboard\ExpensesController@expensesHistory' );

Route::get( '/expenses/categories', 'Dashboard\ExpensesCategoriesController@listExpensesCategories' );
Route::get( '/expenses/categories/create', 'Dashboard\ExpensesCategoriesController@createExpenseCategory' );
Route::get( '/expenses/categories/edit/{category}', 'Dashboard\ExpensesCategoriesController@editExpenseCategory' );

Route::get( '/products', [ ProductsController::class, 'listProducts' ]);
Route::get( '/products/create', [ ProductsController::class, 'createProduct' ]);
Route::get( '/products/stock-adjustment', [ ProductsController::class, 'showStockAdjustment' ]);
Route::get( '/products/edit/{product}', [ ProductsController::class, 'editProduct' ]);
Route::get( '/products/{product}/units', [ ProductsController::class, 'productUnits' ]);
Route::get( '/products/{product}/history', [ ProductsController::class, 'productHistory' ]);
Route::get( '/products/categories', 'Dashboard\CategoryController@listCategories' );
Route::get( '/products/categories/create', 'Dashboard\CategoryController@createCategory' );
Route::get( '/products/categories/edit/{category}', 'Dashboard\CategoryController@editCategory' );

Route::get( '/taxes', 'Dashboard\TaxesController@listTaxes' );
Route::get( '/taxes/create', 'Dashboard\TaxesController@createTax' );
Route::get( '/taxes/edit/{tax}', 'Dashboard\TaxesController@editTax' );
Route::get( '/taxes/groups', 'Dashboard\TaxesController@taxesGroups' );
Route::get( '/taxes/groups/create', 'Dashboard\TaxesController@createTaxGroups' );
Route::get( '/taxes/groups/edit/{group}', 'Dashboard\TaxesController@editTaxGroup' );

Route::get( '/units', 'Dashboard\UnitsController@listUnits' );
Route::get( '/units/edit/{unit}', 'Dashboard\UnitsController@editUnit' );
Route::get( '/units/create', 'Dashboard\UnitsController@createUnit' );
Route::get( '/units/groups', 'Dashboard\UnitsController@listUnitsGroups' );
Route::get( '/units/groups/create', 'Dashboard\UnitsController@createUnitGroup' );
Route::get( '/units/groups/edit/{group}', 'Dashboard\UnitsController@editUnitGroup' );

Route::get( '/users', 'Dashboard\UsersController@listUsers' );
Route::get( '/users/create', 'Dashboard\UsersController@createUser' );
Route::get( '/users/edit/{user}', 'Dashboard\UsersController@editUser' );
Route::get( '/users/roles/permissions-manager', 'Dashboard\UsersController@permissionManager' );
Route::get( '/users/profile', 'Dashboard\UsersController@getProfile' )->name( 'ns.dashboard.users.profile' );
Route::get( '/users/roles', 'Dashboard\UsersController@rolesList' );
Route::get( '/users/roles/{id}', 'Dashboard\UsersController@editRole' );

Route::get( '/settings/{settings}', 'Dashboard\SettingsController@getSettings' );
Route::get( '/settings/form/{settings}', 'Dashboard\SettingsController@loadSettingsForm' );

Route::get( '/experiments', 'DashboardController@experiments' );