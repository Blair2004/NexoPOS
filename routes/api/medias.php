<?php

use App\Http\Controllers\Dashboard\MediasController;
use Illuminate\Support\Facades\Route;

Route::get( 'medias', [ MediasController::class, 'getMedias' ] );
Route::delete( 'medias/{id}', [ MediasController::class, 'deleteMedia' ] );
Route::put( 'medias/{media}', [ MediasController::class, 'updateMedia' ] );
Route::post( 'medias/bulk-delete/', [ MediasController::class, 'bulkDeleteMedias' ] );
Route::post( 'medias', [ MediasController::class, 'uploadMedias' ] );
