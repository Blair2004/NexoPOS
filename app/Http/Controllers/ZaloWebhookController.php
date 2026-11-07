<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Nhận sự kiện từ Zalo OA webhook (POST /api/zalo/webhook).
 *
 * Zalo gọi endpoint này khi có sự kiện trên OA, ví dụ khách theo dõi OA
 * (`follow`) hoặc nhắn tin cho OA (`user_send_text`). Endpoint chỉ ghi log
 * để nhân viên liên kết mã Zalo user ID cho khách (xem docs/zalo-oa.md);
 * webhook không bao giờ tự tạo / sửa đơn hàng nên không cần xác thực
 * chữ ký — Zalo OA webhook hiện không ký request. Nếu sau này Zalo bổ
 * sung chữ ký, hãy kiểm tra với ZALO_OA_SECRET tại đây trước khi xử lý.
 *
 * @see docs/zalo-oa.md
 */
class ZaloWebhookController extends Controller
{
    public function handle( Request $request ): JsonResponse
    {
        $eventName = (string) $request->input( 'event_name', '' );
        $senderId = (string) ( $request->input( 'sender.id' ) ?? $request->input( 'sender_id', '' ) );

        Log::info( 'Zalo OA webhook: event received.', [
            'event_name' => $eventName,
            'sender_id' => $senderId,
        ] );

        if ( $eventName === 'follow' ) {
            $this->handleFollow( $senderId );
        } elseif ( $eventName === 'user_send_text' ) {
            $this->handleUserMessage( $senderId, (string) $request->input( 'message.text', '' ) );
        } else {
            Log::debug( 'Zalo OA webhook: unhandled event, logged only.', [
                'event_name' => $eventName,
            ] );
        }

        /**
         * Zalo yêu cầu trả HTTP 200 để xác nhận đã nhận sự kiện.
         */
        return response()->json( [ 'message' => 'ok' ] );
    }

    /**
     * Khách vừa theo dõi OA: ghi log mã Zalo user ID để nhân viên liên kết
     * với hồ sơ khách bằng `php artisan ns:zalo-link-customer`.
     */
    protected function handleFollow( string $senderId ): void
    {
        if ( $senderId === '' ) {
            return;
        }

        Log::info( 'Zalo OA webhook: new follower. Link this Zalo user ID to a customer with ns:zalo-link-customer.', [
            'zalo_user_id' => $senderId,
        ] );
    }

    /**
     * Khách nhắn tin cho OA: MVP chỉ ghi log. Đặt hàng qua Zalo và trả lời
     * tự động là bước tiếp theo (xem docs/zalo-oa.md).
     */
    protected function handleUserMessage( string $senderId, string $text ): void
    {
        Log::info( 'Zalo OA webhook: customer message received.', [
            'sender_id' => $senderId,
            'text' => $text,
        ] );
    }
}
