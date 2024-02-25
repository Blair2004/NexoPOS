<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Order;
use App\Services\CustomerService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ProcessCustomerOwedAndRewardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

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
    public function handle( CustomerService $customerService )
    {
        $this->order->load( 'customer' );

        if ( $this->order->customer instanceof Customer ) {
            $customerService->updateCustomerOwedAmount( $this->order->customer );
            $customerService->computeReward( $this->order );
            $customerService->increaseCustomerPurchase( $this->order );
        }
    }
}
