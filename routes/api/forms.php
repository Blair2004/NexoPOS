<?php

use App\Http\Controllers\Dashboard\FormsController;
use Illuminate\Support\Facades\Route;

Route::get( '/forms/{resource}/{identifier?}', [ FormsController::class, 'getForm' ] );
Route::post( '/forms/{resource}/{identifier?}', [ FormsController::class, 'saveForm' ] );
