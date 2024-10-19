<?php

use App\Classes\Hook;
use App\Http\Controllers\Dashboard\ModulesController;
use Illuminate\Support\Facades\Route;

Route::get( 'modules/{argument?}', [ ModulesController::class, 'getModules' ] );
Route::put( 'modules/{argument}/disable', [ ModulesController::class, 'disableModule' ] );
Route::put( 'modules/{argument}/enable', [ ModulesController::class, 'enableModule' ] );
Route::delete( 'modules/{argument}/delete', [ ModulesController::class, 'deleteModule' ] );
Route::post( 'modules', [ ModulesController::class, 'uploadModule' ] )->name( Hook::filter( 'ns-route-name', 'ns.dashboard.modules-upload-post' ) );
