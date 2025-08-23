<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get( 'system/fix-symbolic-links', [ DashboardController::class, 'createSymbolicLinks' ] )->name( 'ns.dashboard.system.fix-symbolic-links' );