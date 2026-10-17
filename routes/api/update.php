<?php

use App\Http\Controllers\UpdateController;
use App\Http\Middleware\InstalledStateMiddleware;
use Illuminate\Support\Facades\Route;

Route::post( 'update', [ UpdateController::class, 'runMigration' ] )
    ->middleware( InstalledStateMiddleware::class );
