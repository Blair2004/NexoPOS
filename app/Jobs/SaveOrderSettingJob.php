<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrdersService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SaveOrderSettingJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct( public Order $order )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle( OrdersService $ordersService ): void
    {
        $ordersService->saveOrderSettings( $this->order );
    }
}
