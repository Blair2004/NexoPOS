<?php

use Illuminate\Support\Facades\Route;

Route::get( 'notifications', 'Dashboard\NotificationsController@getNotifications' );
Route::delete( 'notifications/{id}', 'Dashboard\NotificationsController@deleteSingleNotification' )->where([ 'id' => '[0-9]+' ]);
Route::delete( 'notifications/all', 'Dashboard\NotificationsController@deletAllNotifications' );