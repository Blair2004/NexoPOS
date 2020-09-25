<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckMigrationStatus;
use Illuminate\Support\Facades\Route;

Route::post( '/update', 'UpdateController@runMigration' )
    ->withoutMiddleware([ Authenticate::class, CheckMigrationStatus::class ]);