<?php

use App\Http\Controllers\Dashboard\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get( '/settings/{identifier}', [ SettingsController::class, 'getSettingsForm' ] );
Route::post( '/settings/{identifier}', [ SettingsController::class, 'saveSettingsForm' ] );
