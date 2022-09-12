<?php

namespace App\Jobs;

use App\Events\LowStockProductsCountedEvent;
use App\Models\ProductUnitQuantity;
use App\Models\Role;
use App\Services\NotificationService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class DetectLowStockProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, NsSerialize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
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
        $products = ProductUnitQuantity::stockAlertEnabled()
            ->whereRaw( 'low_quantity > quantity' )
            ->count();

        if ( $products > 0 ) {
            LowStockProductsCountedEvent::dispatch();

            /**
             * @var NotificationService
             */
            $notificationService = app()->make( NotificationService::class );
            $notificationService->create([
                'title' => __( 'Low Stock Alert' ),
                'description' => sprintf(
                    __( '%s product(s) has low stock. Check those products to reorder them before the stock reach zero.' ),
                    $products
                ),
                'identifier' => 'ns.low-stock-products',
                'url' => ns()->route( 'ns.dashboard.reports-low-stock' ),
            ])->dispatchForGroupNamespaces([
                Role::ADMIN,
                Role::STOREADMIN,
            ]);
        }
    }
}
