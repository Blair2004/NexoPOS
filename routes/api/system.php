<?php

use App\Http\Controllers\DashboardController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::get( 'system/fix-symbolic-links', [ DashboardController::class, 'createSymbolicLinks' ] )->name( 'ns.dashboard.system.fix-symbolic-links' )->middleware( NsRestrictMiddleware::arguments( 'update.core' ) );
