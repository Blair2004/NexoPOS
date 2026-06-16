<?php

use App\Classes\Hook;
use App\Http\Controllers\Dashboard\ThemesController;
use App\Http\Middleware\NsRestrictMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware([
    NsRestrictMiddleware::arguments('manage.themes'),
])->group(function () {
    Route::get('themes/{argument?}', [ThemesController::class, 'getThemes']);
    Route::put('themes/{argument}/disable', [ThemesController::class, 'disableTheme']);
    Route::put('themes/{argument}/enable', [ThemesController::class, 'enableTheme']);
    Route::delete('themes/{argument}/delete', [ThemesController::class, 'deleteTheme']);
    Route::post('themes/symlink', [ThemesController::class, 'createSymlink'])->name(Hook::filter('ns.route.name', 'ns.dashboard.themes-symlink'));
    Route::post('themes', [ThemesController::class, 'uploadTheme'])->name(Hook::filter('ns-route-name', 'ns.dashboard.themes-upload-post'));
});
