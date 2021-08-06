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
use App\Http\Controllers\Dashboard\CashRegistersController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get( '', [ DashboardController::class, 'home' ])->name( Hook::filter( 'ns-route-name', 'ns.dashboard.home' ) );
    
include( dirname( __FILE__ ) . '/web/orders.php' );
include( dirname( __FILE__ ) . '/web/medias.php' );
include( dirname( __FILE__ ) . '/web/customers.php' );
include( dirname( __FILE__ ) . '/web/cash-registers.php' );
include( dirname( __FILE__ ) . '/web/procurements.php' );
include( dirname( __FILE__ ) . '/web/providers.php' );
include( dirname( __FILE__ ) . '/web/settings.php' );
include( dirname( __FILE__ ) . '/web/expenses.php' );
include( dirname( __FILE__ ) . '/web/products.php' );
include( dirname( __FILE__ ) . '/web/taxes.php' );
include( dirname( __FILE__ ) . '/web/units.php' );
include( dirname( __FILE__ ) . '/web/reports.php' );
include( dirname( __FILE__ ) . '/web/banking.php' );