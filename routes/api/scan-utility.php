<?php

use App\Http\Controllers\Dashboard\ScanUtilityController;

Route::prefix( 'scan-utility' )->group( function () {
    Route::get( 'barcode-reader/state', [ ScanUtilityController::class, 'getBarcodeReaderState' ] );
});