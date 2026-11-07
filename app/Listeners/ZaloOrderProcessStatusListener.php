<?php

namespace App\Listeners;

use App\Events\OrderAfterUpdatedProcessStatus;
use App\Services\ZaloOaService;
use Throwable;

/**
 * Gửi cập nhật trạng thái món qua Zalo OA khi trạng thái xử lý của đơn đổi
 * (pending -> ongoing -> ready). Dùng cho bếp / quầy khi món xong.
 *
 * Listener này được Laravel tự phát hiện (EventServiceProvider bật
 * shouldDiscoverEvents). Lỗi Zalo không bao giờ được làm gián đoạn
 * luồng cập nhật đơn: mọi exception đều được bắt và ghi log.
 */
class ZaloOrderProcessStatusListener
{
    public function __construct(
        public ZaloOaService $zalo,
    ) {}

    public function handle( OrderAfterUpdatedProcessStatus $event ): void
    {
        if ( ! $this->zalo->isEnabled() ) {
            return;
        }

        try {
            $this->zalo->sendOrderStatusUpdate( $event->order );
        } catch ( Throwable $exception ) {
            report( $exception );
        }
    }
}
