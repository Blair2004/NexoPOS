<?php

use App\Http\Controllers\Dashboard\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get( '/settings/{settings}', [ SettingsController::class, 'getSettings' ] )->name( ns()->routeName( 'ns.dashboard.settings' ) );
Route::get( '/settings/form/{settings}', [ SettingsController::class, 'loadSettingsForm' ] )->name( ns()->routeName( 'ns.dashboard.settings.form' ) );
