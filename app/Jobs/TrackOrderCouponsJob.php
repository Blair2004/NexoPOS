<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrdersService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class TrackOrderCouponsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, NsSerialize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Order $order )
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /**
         * @var OrdersService
         */
        $orderService = app()->make( OrdersService::class );

        $orderService->trackOrderCoupons( $this->order );
    }
}
