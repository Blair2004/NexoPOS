<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ZaloOaService;
use Illuminate\Console\Command;

/**
 * Gửi thử một tin nhắn Zalo OA.
 *
 * Mặc định luôn chạy thử (dry-run): chỉ ghi log, không gọi API thật.
 * Thêm --live để gửi thật (yêu cầu đã cấu hình ZALO_OA_ID và
 * ZALO_OA_ACCESS_TOKEN, và người nhận đã theo dõi OA).
 *
 * Ví dụ:
 *  php artisan ns:zalo-test --recipient=123456789 --message="Xin chào"
 *  php artisan ns:zalo-test --order=12
 *  php artisan ns:zalo-test --order=12 --live
 */
class ZaloTestMessageCommand extends Command
{
    protected $signature = 'ns:zalo-test
        {--recipient= : Zalo user ID của người nhận}
        {--order= : ID đơn hàng, gửi nội dung xác nhận đơn cho khách của đơn đó}
        {--message= : Nội dung tin nhắn tùy ý}
        {--live : Gửi thật qua API Zalo (mặc định chỉ chạy thử)}';

    protected $description = 'Send a test message through Zalo OA (dry-run by default).';

    public function handle( ZaloOaService $zalo ): int
    {
        if ( ! $this->option( 'live' ) ) {
            config( [ 'services.zalo.dry_run' => true ] );

            $this->info( __( 'Zalo OA dry run: no message was actually sent.' ) );
        }

        if ( ! $zalo->isEnabled() ) {
            $this->warn( __( 'Zalo OA is disabled. Set ZALO_OA_ENABLED=true to enable it.' ) );
        }

        if ( ! $zalo->isConfigured() ) {
            $this->error( __( 'Zalo OA is not configured. Set ZALO_OA_ID and ZALO_OA_ACCESS_TOKEN first.' ) );

            return self::FAILURE;
        }

        $orderId = $this->option( 'order' );

        if ( $orderId ) {
            $order = Order::with( 'customer' )->find( $orderId );

            if ( ! $order ) {
                $this->error( __( 'The requested order could not be found.' ) );

                return self::FAILURE;
            }

            $result = $zalo->sendOrderConfirmation( $order );
        } else {
            $recipientId = (string) $this->option( 'recipient' );
            $message = (string) ( $this->option( 'message' ) ?: __( 'Test message from NexoPOS via Zalo OA.' ) );

            if ( $recipientId === '' ) {
                $this->error( __( 'Please provide a recipient with --recipient or an order with --order.' ) );

                return self::FAILURE;
            }

            $result = $zalo->sendTextMessage( $recipientId, $message );
        }

        if ( $result[ 'sent' ] ?? false ) {
            $this->info( __( 'Zalo OA test message sent.' ) );

            return self::SUCCESS;
        }

        $this->error( __( 'Zalo OA message was not sent.' ) . ' (' . ( $result[ 'reason' ] ?? 'unknown' ) . ')' );

        return self::FAILURE;
    }
}
