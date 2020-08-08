<?php

use Illuminate\Support\Facades\Route;

Route::get( '/settings/{form}', 'Dashboard\SettingsController@getSettingsForm' );