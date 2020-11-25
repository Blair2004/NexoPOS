<?php

use App\Http\Controllers\Dashboard\TransfersController;
use Illuminate\Support\Facades\Route;

Route::get( 'transfer/{id?}', [ TransfersController::class, 'list' ]);
Route::post( 'transfer', [ TransfersController::class, 'create' ]);
Route::put( 'transfer/{id}', [ TransfersController::class, 'edit' ]);
Route::delete( 'transfer/{id}', [ TransfersController::class, 'delete' ]);