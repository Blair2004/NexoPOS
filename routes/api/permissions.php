<?php

use App\Http\Controllers\Dashboard\PermissionController;
use Illuminate\Support\Facades\Route;

Route::get( 'permissions/granted', [ PermissionController::class, 'getAllGrandedPermissions' ] );
Route::get( 'permissions/{namespace}', [ PermissionController::class, 'getSinglePermission' ] );
