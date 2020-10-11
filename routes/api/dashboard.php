<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get( 'dashboard/day', [ DashboardController::class, 'getCards' ]);