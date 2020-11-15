<?php
// Route::get( 'reset', 'Dashboard\ResetController@truncateAllTables' );

use Illuminate\Support\Facades\Route;

Route::post( 'reset', 'Dashboard\ResetController@truncateWithDemo' );