<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get( '', [ DashboardController::class, 'home' ])->name( ns()->routeName( 'ns.dashboard.home' ) );

include dirname( __FILE__ ) . '/web/orders.php';
include dirname( __FILE__ ) . '/web/medias.php';
include dirname( __FILE__ ) . '/web/customers.php';
include dirname( __FILE__ ) . '/web/cash-registers.php';
include dirname( __FILE__ ) . '/web/procurements.php';
include dirname( __FILE__ ) . '/web/providers.php';
include dirname( __FILE__ ) . '/web/settings.php';
include dirname( __FILE__ ) . '/web/expenses.php';
include dirname( __FILE__ ) . '/web/products.php';
include dirname( __FILE__ ) . '/web/taxes.php';
include dirname( __FILE__ ) . '/web/units.php';
include dirname( __FILE__ ) . '/web/reports.php';
include dirname( __FILE__ ) . '/web/banking.php';
