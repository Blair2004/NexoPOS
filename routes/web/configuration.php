<?php

use App\Http\Controllers\DashboardController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::get( 'configure', [ DashboardController::class, 'configure' ])->name( ns()->routeName( 'ns.dashboard.configure' ) )
    ->middleware( [ NsRestrictMiddleware::arguments( 'manage.options' )] );