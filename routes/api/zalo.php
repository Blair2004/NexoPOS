<?php

use App\Http\Controllers\ZaloWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * Webhook nhận sự kiện từ Zalo OA. Route công khai (không cần đăng nhập)
 * vì Zalo gọi từ phía máy chủ của họ, nhưng chỉ ghi log và không bao giờ
 * tạo hay sửa đơn hàng.
 */
Route::post( 'zalo/webhook', [ ZaloWebhookController::class, 'handle' ] );
