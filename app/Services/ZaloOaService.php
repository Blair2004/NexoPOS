<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Gửi thông báo cho khách hàng qua Zalo OA (Official Account).
 *
 * Luồng MVP:
 *  - Xác nhận đơn khi đơn được tạo, cập nhật trạng thái món (pending /
 *    ongoing / ready), xác nhận thanh toán + hóa đơn khi đơn được thanh toán.
 *  - Khuyến mãi gửi cho khách quen đã liên kết tài khoản Zalo.
 *
 * Zalo OA chỉ cho phép nhắn cho người dùng đã theo dõi (follow) OA, và tin
 * nhắn CS cần `user_id` của Zalo (không gửi được bằng số điện thoại). Mã
 * Zalo user ID được lưu ở cột `zalo_user_id` của khách hàng (xem migration
 * 2026_11_07_000000_add_zalo_user_id_to_users_table) và liên kết bằng lệnh
 * `php artisan ns:zalo-link-customer` hoặc từ sự kiện `follow` của webhook.
 *
 * An toàn: không có secret nào được hardcode. OA ID và access token chỉ đọc
 * từ biến môi trường qua config/services.php. Khi chưa bật
 * (ZALO_OA_ENABLED=true) hoặc đang dry-run (ZALO_OA_DRY_RUN=true, mặc định),
 * service chỉ ghi log và không gọi API thật.
 *
 * @see docs/zalo-oa.md
 */
class ZaloOaService
{
    /**
     * Endpoint gửi tin nhắn CS của Zalo OA Open API.
     */
    public const MESSAGE_ENDPOINT = '/v3.0/oa/message/cs';

    /**
     * Header chữ ký của Zalo webhook.
     */
    public const WEBHOOK_SIGNATURE_HEADER = 'X-ZEvent-Signature';

    public function isEnabled(): bool
    {
        return (bool) config( 'services.zalo.enabled', false );
    }

    /**
     * Có bắt buộc xác minh chữ ký webhook không.
     *
     * Mặc định TẮT (log-only): công thức chữ ký
     * sha256(appId + rawBody + timestamp + secret) được nhiều nguồn
     * độc lập xác nhận (kể cả tài liệu Zalo được DeepWiki trích dẫn),
     * nhưng chưa được kiểm chứng bằng một sự kiện có chữ ký thật từ
     * Zalo. Bật cưỡng chế khi công thức còn sai sẽ làm Zalo trả 401
     * cho 100% sự kiện thật, nên chỉ bật
     * ZALO_OA_WEBHOOK_ENFORCE=true sau khi đã thấy log
     * "signature verified" từ một sự kiện thật.
     */
    public function isWebhookEnforcementEnabled(): bool
    {
        return (bool) config( 'services.zalo.webhook_enforce', false );
    }

    /**
     * Xác minh chữ ký webhook của Zalo OA.
     *
     * Công thức (theo tài liệu Zalo, xem docs/zalo-oa.md):
     *   mac = sha256(appId + rawBody + timestamp + OAsecretKey)
     * với rawBody là body JSON gốc (không serialize lại), timestamp
     * là trường `timestamp` trong body, header mang dạng `mac=<hex>`.
     * Không bao giờ log secret hay MAC kỳ vọng.
     *
     * Trả về [ 'valid' => bool, 'reason' => string ] với reason là
     * một trong: ok, secret_not_configured, signature_missing,
     * invalid_json, app_id_or_timestamp_missing, signature_mismatch.
     */
    public function verifyWebhookSignature( string $rawBody, ?string $signatureHeader ): array
    {
        $secret = (string) config( 'services.zalo.secret', '' );

        if ( $secret === '' ) {
            return [ 'valid' => false, 'reason' => 'secret_not_configured' ];
        }

        if ( $signatureHeader === null || trim( $signatureHeader ) === '' ) {
            return [ 'valid' => false, 'reason' => 'signature_missing' ];
        }

        $payload = json_decode( $rawBody, true );

        if ( ! is_array( $payload ) ) {
            return [ 'valid' => false, 'reason' => 'invalid_json' ];
        }

        $appId = (string) ( $payload[ 'app_id' ] ?? $payload[ 'data' ][ 'app_id' ] ?? config( 'services.zalo.app_id', '' ) );
        $timestamp = (string) ( $payload[ 'timestamp' ] ?? $payload[ 'data' ][ 'timestamp' ] ?? '' );

        if ( $appId === '' || $timestamp === '' ) {
            return [ 'valid' => false, 'reason' => 'app_id_or_timestamp_missing' ];
        }

        $provided = trim( (string) preg_replace( '/^mac\s*=\s*/i', '', trim( $signatureHeader ) ) );

        if ( $provided === '' ) {
            return [ 'valid' => false, 'reason' => 'signature_mismatch' ];
        }

        $expected = hash( 'sha256', $appId . $rawBody . $timestamp . $secret );

        if ( ! hash_equals( $expected, strtolower( $provided ) ) ) {
            return [ 'valid' => false, 'reason' => 'signature_mismatch' ];
        }

        return [ 'valid' => true, 'reason' => 'ok' ];
    }

    public function isDryRun(): bool
    {
        return (bool) config( 'services.zalo.dry_run', true );
    }

    public function isConfigured(): bool
    {
        return ! empty( config( 'services.zalo.oa_id' ) )
            && ! empty( config( 'services.zalo.access_token' ) );
    }

    public function getApiBaseUrl(): string
    {
        return rtrim( (string) config( 'services.zalo.api_base_url', 'https://openapi.zalo.me' ), '/' );
    }

    /**
     * Mã người nhận trên Zalo của khách hàng (null khi chưa liên kết).
     */
    public function resolveRecipientId( Customer $customer ): ?string
    {
        $zaloUserId = $customer->zalo_user_id ?? null;

        return $zaloUserId ? (string) $zaloUserId : null;
    }

    /**
     * Tên hiển thị của khách hàng.
     */
    public function getCustomerName( Customer $customer ): string
    {
        $name = trim( ( $customer->first_name ?? '' ) . ' ' . ( $customer->last_name ?? '' ) );

        return $name !== '' ? $name : ( $customer->username ?? __( 'Customer' ) );
    }

    /**
     * Nhãn trạng thái thanh toán dùng trong tin nhắn.
     */
    public function getPaymentStatusLabel( string $status ): string
    {
        return match ( $status ) {
            Order::PAYMENT_PAID => __( 'Paid' ),
            Order::PAYMENT_UNPAID => __( 'Unpaid' ),
            Order::PAYMENT_PARTIALLY => __( 'Partially Paid' ),
            Order::PAYMENT_HOLD => __( 'Hold' ),
            Order::PAYMENT_REFUNDED => __( 'Refunded' ),
            Order::PAYMENT_DUE => __( 'Due' ),
            default => $status,
        };
    }

    /**
     * Nhãn trạng thái món / xử lý đơn dùng trong tin nhắn.
     */
    public function getProcessStatusLabel( string $status ): string
    {
        return match ( $status ) {
            Order::PROCESSING_PENDING => __( 'Pending' ),
            Order::PROCESSING_ONGOING => __( 'Being prepared' ),
            Order::PROCESSING_READY => __( 'Ready' ),
            Order::PROCESSING_FAILED => __( 'Failed' ),
            default => $status,
        };
    }

    /**
     * Định dạng tiền theo cấu hình cửa hàng, dự phòng khi chưa cấu hình.
     */
    public function formatAmount( float $amount ): string
    {
        try {
            return CurrencyService::define( $amount )->format();
        } catch ( Throwable $exception ) {
            Log::debug( 'Zalo OA: cannot format amount with the store currency, using fallback.', [
                'message' => $exception->getMessage(),
            ] );

            return number_format( $amount, 0, ',', '.' ) . ' ₫';
        }
    }

    /**
     * Nội dung xác nhận đơn hàng.
     */
    public function buildOrderConfirmationMessage( Order $order ): string
    {
        $customer = $order->customer;
        $name = $customer ? $this->getCustomerName( $customer ) : __( 'Customer' );

        return implode( "\n", [
            sprintf( __( 'Hello %s, your order %s has been confirmed.' ), $name, $order->code ),
            sprintf( __( 'Total: %s' ), $this->formatAmount( (float) $order->total ) ),
            sprintf( __( 'Payment status: %s' ), $this->getPaymentStatusLabel( (string) $order->payment_status ) ),
            __( 'Thank you for shopping with us!' ),
        ] );
    }

    /**
     * Nội dung cập nhật trạng thái món / xử lý đơn.
     */
    public function buildOrderStatusMessage( Order $order, ?string $status = null ): string
    {
        $statusLabel = $this->getProcessStatusLabel( $status ?? (string) $order->process_status );

        return implode( "\n", [
            sprintf( __( 'Your order %s is now %s.' ), $order->code, $statusLabel ),
            sprintf( __( 'Total: %s' ), $this->formatAmount( (float) $order->total ) ),
            __( 'Thank you for shopping with us!' ),
        ] );
    }

    /**
     * Nội dung xác nhận đã nhận thanh toán.
     */
    public function buildPaymentMessage( Order $order ): string
    {
        return implode( "\n", [
            sprintf(
                __( 'Your payment of %s for order %s has been received.' ),
                $this->formatAmount( (float) $order->total ),
                $order->code
            ),
            __( 'Thank you for shopping with us!' ),
        ] );
    }

    /**
     * Nội dung hóa đơn của đơn hàng.
     */
    public function buildInvoiceMessage( Order $order ): string
    {
        return implode( "\n", [
            sprintf(
                __( 'Your invoice for order %s is ready. Total: %s.' ),
                $order->code,
                $this->formatAmount( (float) $order->total )
            ),
            __( 'Thank you for shopping with us!' ),
        ] );
    }

    /**
     * Nội dung khuyến mãi gửi khách quen.
     */
    public function buildPromotionMessage( Customer $customer, string $offer ): string
    {
        return sprintf(
            __( 'Hello %s! We have a special offer for you: %s' ),
            $this->getCustomerName( $customer ),
            $offer
        );
    }

    /**
     * Gửi một tin nhắn văn bản qua Zalo OA.
     *
     * Trả về mảng kết quả, không ném exception để không làm gián đoạn
     * luồng bán hàng khi Zalo lỗi:
     *  - [ 'sent' => false, 'reason' => 'disabled' ] khi chưa bật.
     *  - [ 'sent' => false, 'reason' => 'not_configured' ] khi thiếu OA ID/token.
     *  - [ 'sent' => true, 'dry_run' => true ] khi chạy thử (không gọi API).
     *  - [ 'sent' => bool, 'response' => [...] ] khi gọi API thật.
     */
    public function sendTextMessage( string $recipientId, string $message ): array
    {
        if ( ! $this->isEnabled() ) {
            Log::info( 'Zalo OA: skipped, integration is disabled.', [
                'recipient_id' => $recipientId,
            ] );

            return [ 'sent' => false, 'reason' => 'disabled' ];
        }

        if ( ! $this->isConfigured() ) {
            Log::warning( 'Zalo OA: skipped, OA ID or access token is not configured.' );

            return [ 'sent' => false, 'reason' => 'not_configured' ];
        }

        if ( $this->isDryRun() ) {
            Log::info( 'Zalo OA [dry-run]: message not sent to the real API.', [
                'recipient_id' => $recipientId,
                'message' => $message,
            ] );

            return [ 'sent' => true, 'dry_run' => true, 'recipient_id' => $recipientId, 'message' => $message ];
        }

        try {
            /**
             * Zalo OA OpenAPI đọc token từ header `access_token`,
             * KHÔNG phải Authorization: Bearer. Dùng withToken()
             * trước đây gửi sai header nên mọi lần gửi live đều bị
             * Zalo từ chối (api_error).
             */
            $response = Http::withHeaders( [
                    'access_token' => (string) config( 'services.zalo.access_token' ),
                ] )
                ->timeout( (int) config( 'services.zalo.timeout', 10 ) )
                ->acceptJson()
                ->post( $this->getApiBaseUrl() . self::MESSAGE_ENDPOINT, [
                    'recipient' => [ 'user_id' => $recipientId ],
                    'message' => [ 'text' => $message ],
                ] );

            $payload = $response->json();

            if ( $response->successful() && ( $payload[ 'error' ] ?? null ) === 0 ) {
                Log::info( 'Zalo OA: message sent.', [ 'recipient_id' => $recipientId ] );

                return [ 'sent' => true, 'dry_run' => false, 'response' => $payload ];
            }

            Log::warning( 'Zalo OA: API rejected the message.', [
                'recipient_id' => $recipientId,
                'status' => $response->status(),
                'response' => $payload,
            ] );

            return [ 'sent' => false, 'reason' => 'api_error', 'response' => $payload ];
        } catch ( Throwable $exception ) {
            Log::error( 'Zalo OA: failed to send the message.', [
                'recipient_id' => $recipientId,
                'message' => $exception->getMessage(),
            ] );

            return [ 'sent' => false, 'reason' => 'exception', 'error' => $exception->getMessage() ];
        }
    }

    /**
     * Gửi xác nhận đơn cho khách của đơn hàng (bỏ qua khi chưa liên kết Zalo).
     */
    public function sendOrderConfirmation( Order $order ): array
    {
        return $this->sendOrderMessage( $order, $this->buildOrderConfirmationMessage( $order ) );
    }

    /**
     * Gửi cập nhật trạng thái món / xử lý đơn.
     */
    public function sendOrderStatusUpdate( Order $order, ?string $status = null ): array
    {
        return $this->sendOrderMessage( $order, $this->buildOrderStatusMessage( $order, $status ) );
    }

    /**
     * Gửi xác nhận thanh toán kèm hóa đơn.
     */
    public function sendPaymentUpdate( Order $order ): array
    {
        return $this->sendOrderMessage( $order, implode( "\n", [
            $this->buildPaymentMessage( $order ),
            $this->buildInvoiceMessage( $order ),
        ] ) );
    }

    /**
     * Gửi khuyến mãi cho một khách quen đã liên kết Zalo.
     */
    public function sendPromotion( Customer $customer, string $offer ): array
    {
        $recipientId = $this->resolveRecipientId( $customer );

        if ( ! $recipientId ) {
            Log::info( 'Zalo OA: no Zalo account linked to this customer, promotion skipped.', [
                'customer_id' => $customer->id,
            ] );

            return [ 'sent' => false, 'reason' => 'no_recipient' ];
        }

        return $this->sendTextMessage( $recipientId, $this->buildPromotionMessage( $customer, $offer ) );
    }

    /**
     * Gửi tin nhắn cho khách của đơn hàng, bỏ qua lặng lẽ khi chưa liên kết.
     */
    protected function sendOrderMessage( Order $order, string $message ): array
    {
        $customer = $order->customer;

        if ( ! $customer ) {
            Log::info( 'Zalo OA: order has no customer, message skipped.', [
                'order_code' => $order->code,
            ] );

            return [ 'sent' => false, 'reason' => 'no_customer' ];
        }

        $recipientId = $this->resolveRecipientId( $customer );

        if ( ! $recipientId ) {
            Log::info( 'Zalo OA: no Zalo account linked to this customer, message skipped.', [
                'customer_id' => $customer->id,
                'order_code' => $order->code,
            ] );

            return [ 'sent' => false, 'reason' => 'no_recipient' ];
        }

        return $this->sendTextMessage( $recipientId, $message );
    }
}
