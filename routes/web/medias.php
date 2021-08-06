<?php

use App\Http\Controllers\Dashboard\MediasController;
use Illuminate\Support\Facades\Route;

Route::get( '/medias', [ MediasController::class, 'showMedia' ]);