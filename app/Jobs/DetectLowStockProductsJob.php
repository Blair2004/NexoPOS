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
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

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
    public function handle( NotificationService $notificationService )
    {
        $products = ProductUnitQuantity::stockAlertEnabled()
            ->whereRaw( 'low_quantity > quantity' )
            ->count();

        if ( $products > 0 ) {
            LowStockProductsCountedEvent::dispatch();

            $notificationService->create( [
                'title' => __( 'Low Stock Alert' ),
                'description' => sprintf(
                    __( '%s product(s) has low stock. Reorder those product(s) before it get exhausted.' ),
                    $products
                ),
                'identifier' => 'ns.low-stock-products',
                'url' => ns()->route( 'ns.dashboard.reports-low-stock' ),
            ] )->dispatchForGroupNamespaces( [
                Role::ADMIN,
                Role::STOREADMIN,
            ] );
        }
    }
}
