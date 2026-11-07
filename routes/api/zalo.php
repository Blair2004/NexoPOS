<?php

use App\Http\Controllers\ZaloWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * Webhook nhận sự kiện từ Zalo OA. Route công khai (không cần đăng nhập)
 * vì Zalo gọi từ phía máy chủ của họ, nhưng chỉ ghi log và không bao giờ
 * tạo hay sửa đơn hàng. Chữ ký webhook (header X-ZEvent-Signature) được
 * xác minh trong ZaloWebhookController bằng secret đã cấu hình; chế độ
 * cưỡng chế (trả 401 khi chữ ký sai) bật bằng ZALO_OA_WEBHOOK_ENFORCE=true
 * sau khi đã kiểm chứng công thức chữ ký với một sự kiện thật.
 */
Route::post( 'zalo/webhook', [ ZaloWebhookController::class, 'handle' ] );
