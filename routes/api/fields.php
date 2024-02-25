<?php

use App\Http\Controllers\Dashboard\FieldsController;
use Illuminate\Support\Facades\Route;

Route::get( '/fields/{resource}/{identifier?}', [ FieldsController::class, 'getFields' ] );
