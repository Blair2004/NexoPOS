<?php

use App\Http\Controllers\Dashboard\PermissionController;
use App\Http\Controllers\Dashboard\UsersController;
use Illuminate\Support\Facades\Route;

Route::get( 'permissions/{namespace}', [ PermissionController::class, 'getSinglePermission' ] );
Route::get( 'permissions/granted', [ PermissionController::class, 'getAllGrandedPermissions' ] );