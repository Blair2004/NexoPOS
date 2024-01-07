<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrdersService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TrackLaidAwayOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( OrdersService $ordersService )
    {
        /**
         * for order thas has already expired
         */
        $ordersService->notifyExpiredLaidAway();
    }
}
