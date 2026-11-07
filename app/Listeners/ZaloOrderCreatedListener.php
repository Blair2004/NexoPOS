<?php

namespace App\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Services\ZaloOaService;
use Throwable;

/**
 * Gửi xác nhận đơn qua Zalo OA khi đơn hàng được tạo.
 *
 * Listener này được Laravel tự phát hiện (EventServiceProvider bật
 * shouldDiscoverEvents). Lỗi Zalo không bao giờ được làm gián đoạn
 * luồng tạo đơn: mọi exception đều được bắt và ghi log.
 */
class ZaloOrderCreatedListener
{
    public function __construct(
        public ZaloOaService $zalo,
    ) {}

    public function handle( OrderAfterCreatedEvent $event ): void
    {
        if ( ! $this->zalo->isEnabled() ) {
            return;
        }

        try {
            $this->zalo->sendOrderConfirmation( $event->order );
        } catch ( Throwable $exception ) {
            report( $exception );
        }
    }
}
