<?php

use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::get( '/settings/{settings}', [ SettingsController::class, 'getSettings' ] )
    ->middleware( NsRestrictMiddleware::arguments( 'manage.options' ) )
    ->name( ns()->routeName( 'ns.dashboard.settings' ) );
