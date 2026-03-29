<?php

use App\Http\Controllers\Dashboard\ThemesController;
use Illuminate\Support\Facades\Route;

Route::get('/themes', [ThemesController::class, 'listThemes'])->name('ns.dashboard.themes-list');
Route::get('/themes/upload', [ThemesController::class, 'showUploadTheme'])->name('ns.dashboard.themes-upload');
Route::get('/themes/download/{identifier}', [ThemesController::class, 'downloadTheme'])->name('ns.dashboard.themes-download');
