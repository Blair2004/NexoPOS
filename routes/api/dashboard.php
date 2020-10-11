<?php

use Illuminate\Support\Facades\Route;

Route::get( 'dashboard/day', 'DashboardController@getCards' );