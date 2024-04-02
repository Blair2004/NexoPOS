<?php

use App\Http\Controllers\Dashboard\ModulesController;
use Illuminate\Support\Facades\Route;

Route::get( '/modules', [ ModulesController::class, 'listModules' ] )->name( 'ns.dashboard.modules-list' );
Route::get( '/modules/upload', [ ModulesController::class, 'showUploadModule' ] )->name( 'ns.dashboard.modules-upload' );
Route::get( '/modules/download/{identifier}', [ ModulesController::class, 'downloadModule' ] )->name( 'ns.dashboard.modules-download' );
Route::get( '/modules/migrate/{namespace}', [ ModulesController::class, 'migrateModule' ] )->name( 'ns.dashboard.modules-migrate' );
