<?php

namespace App\Http\Controllers;

use App\Services\ZaloOaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Nhận sự kiện từ Zalo OA webhook (POST /api/zalo/webhook).
 *
 * Zalo gọi endpoint này khi có sự kiện trên OA, ví dụ khách theo dõi OA
 * (`follow`) hoặc nhắn tin cho OA (`user_send_text`). Endpoint chỉ ghi log
 * để nhân viên liên kết mã Zalo user ID cho khách (xem docs/zalo-oa.md);
 * webhook không bao giờ tự tạo / sửa đơn hàng.
 *
 * Xác minh chữ ký: Zalo ký mọi sự kiện webhook bằng header
 * `X-ZEvent-Signature: mac=<hex>`, với
 *   mac = sha256(appId + rawBody + timestamp + OAsecretKey)
 * (rawBody là body JSON gốc, timestamp là trường `timestamp` trong body).
 * Chữ ký được kiểm tra trong ZaloOaService::verifyWebhookSignature().
 * Lưu ý: công thức này được nhiều nguồn độc lập xác nhận (kể cả tài liệu
 * Zalo được DeepWiki trích dẫn), nhưng chưa được kiểm chứng bằng một sự
 * kiện có chữ ký thật từ Zalo, nên chế độ cưỡng chế mặc định TẮT:
 * khi chưa bật ZALO_OA_WEBHOOK_ENFORCE=true, chữ ký sai chỉ được ghi
 * log cảnh báo mà request vẫn được xử lý (hành vi cũ). Chỉ bật cưỡng
 * chế sau khi đã thấy log "signature verified" từ một sự kiện thật —
 * xem docs/zalo-oa.md. Không bao giờ log secret hay MAC kỳ vọng.
 *
 * @see docs/zalo-oa.md
 */
class ZaloWebhookController extends Controller
{
    public function __construct(
        private ZaloOaService $zaloService
    ) {
        //
    }

    public function handle( Request $request ): JsonResponse
    {
        /**
         * Xác minh chữ ký trên raw body, TRƯỚC khi xử lý sự kiện.
         * Không dùng JSON serialize lại vì sẽ làm lệch chữ ký.
         */
        $verdict = $this->zaloService->verifyWebhookSignature(
            (string) $request->getContent(),
            $request->header( ZaloOaService::WEBHOOK_SIGNATURE_HEADER )
        );

        if ( ! $verdict[ 'valid' ] ) {
            Log::warning( 'Zalo OA webhook: signature verification failed.', [
                'reason' => $verdict[ 'reason' ],
            ] );

            if ( $this->zaloService->isWebhookEnforcementEnabled() ) {
                return response()->json( [ 'message' => 'Unauthorized' ], 401 );
            }
        } else {
            Log::debug( 'Zalo OA webhook: signature verified.' );
        }

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
     * Nội dung tin được cắt ngắn và bỏ ký tự xuống dòng để tránh
     * log-injection (tin nhắn có thể chứa PII).
     */
    protected function handleUserMessage( string $senderId, string $text ): void
    {
        Log::info( 'Zalo OA webhook: customer message received.', [
            'sender_id' => $senderId,
            'text' => Str::limit( (string) preg_replace( '/[\r\n]+/', ' ', $text ), 200 ),
        ] );
    }
}
