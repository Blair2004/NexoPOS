<?php

use App\Http\Controllers\Dashboard\NotificationsController;
use Illuminate\Support\Facades\Route;

Route::get( 'notifications', [ NotificationsController::class, 'getNotifications' ] );
Route::delete( 'notifications/{id}', [ NotificationsController::class, 'deleteSingleNotification' ] )->where( [ 'id' => '[0-9]+' ] );
Route::delete( 'notifications/all', [ NotificationsController::class, 'deletAllNotifications' ] );
