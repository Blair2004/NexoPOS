<?php

/**
 * QR Order — Tính năng 1: QR order tại bàn + đặt món online.
 * Route công khai, không cần đăng nhập (xem QrOrderController).
 * Đặt trong nhóm middleware "ns.installed" ở routes/web-base.php.
 */

use App\Http\Controllers\QrOrderController;
use Illuminate\Support\Facades\Route;

Route::prefix( 'dat-mon' )->group( function () {
    Route::get( '/', [ QrOrderController::class, 'menu' ] )
        ->name( 'ns.qr-order.menu' );

    Route::get( '/ban/{table}', [ QrOrderController::class, 'menu' ] )
        ->where( 'table', '[A-Za-z0-9\-]{1,20}' )
        ->name( 'ns.qr-order.table-menu' );

    Route::get( '/don/{code}', [ QrOrderController::class, 'show' ] )
        ->where( 'code', '[A-Za-z0-9\-]+' )
        ->name( 'ns.qr-order.status' );

    Route::post( '/', [ QrOrderController::class, 'store' ] )
        ->middleware( 'throttle:30,1' )
        ->name( 'ns.qr-order.store' );

    Route::post( '/yeu-cau', [ QrOrderController::class, 'requestService' ] )
        ->middleware( 'throttle:10,1' )
        ->name( 'ns.qr-order.request' );
} );
