<?php

use App\Http\Controllers\Dashboard\ResetController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::post( 'reset', [ ResetController::class, 'truncateWithDemo' ] )->name( 'ns.reset' )->middleware( NsRestrictMiddleware::arguments( 'manage.options' ) );
