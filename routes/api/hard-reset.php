<?php
use App\Http\Controllers\Dashboard\ResetController;
use Illuminate\Support\Facades\Route;

Route::post( 'hard-reset', [ ResetController::class, 'hardReset' ])->name( 'ns.hard-reset' );