<?php

use App\Http\Controllers\UpdateController;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckMigrationStatus;
use Illuminate\Support\Facades\Route;

Route::post( 'update', [ UpdateController::class, 'runMigration' ] )
    ->withoutMiddleware( [ Authenticate::class, CheckMigrationStatus::class ] );
