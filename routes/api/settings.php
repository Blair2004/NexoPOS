<?php

use App\Http\Controllers\Dashboard\SettingsController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::get( '/settings/{identifier}', [ SettingsController::class, 'getSettingsForm' ] )->middleware( NsRestrictMiddleware::arguments( 'manage.options' ) );
Route::post( '/settings/{identifier}', [ SettingsController::class, 'saveSettingsForm' ] )->middleware( NsRestrictMiddleware::arguments( 'manage.options' ) );
