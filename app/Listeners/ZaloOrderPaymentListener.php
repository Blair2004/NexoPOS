<?php

namespace App\Listeners;

use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Models\Order;
use App\Services\ZaloOaService;
use Throwable;

/**
 * Gửi xác nhận thanh toán + hóa đơn qua Zalo OA khi đơn được thanh toán.
 *
 * Listener này được Laravel tự phát hiện (EventServiceProvider bật
 * shouldDiscoverEvents). Lỗi Zalo không bao giờ được làm gián đoạn
 * luồng thanh toán: mọi exception đều được bắt và ghi log.
 */
class ZaloOrderPaymentListener
{
    public function __construct(
        public ZaloOaService $zalo,
    ) {}

    public function handle( OrderAfterPaymentStatusChangedEvent $event ): void
    {
        if ( ! $this->zalo->isEnabled() ) {
            return;
        }

        if ( $event->new !== Order::PAYMENT_PAID ) {
            return;
        }

        try {
            $this->zalo->sendPaymentUpdate( $event->order );
        } catch ( Throwable $exception ) {
            report( $exception );
        }
    }
}
