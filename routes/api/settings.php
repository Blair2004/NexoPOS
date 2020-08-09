<?php

use Illuminate\Support\Facades\Route;

Route::get( '/settings/{identifier}', 'Dashboard\SettingsController@getSettingsForm' );
Route::post( '/settings/{identifier}', 'Dashboard\SettingsController@saveSettingsForm' );