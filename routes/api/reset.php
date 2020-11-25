<?php
// Route::get( 'reset', 'Dashboard\ResetController@truncateAllTables' );

use App\Http\Controllers\Dashboard\ResetController;
use Illuminate\Support\Facades\Route;

Route::post( 'reset', [ ResetController::class, 'truncateWithDemo' ]);