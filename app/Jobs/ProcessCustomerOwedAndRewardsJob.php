<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CustomerService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCustomerOwedAndRewardsJob implements ShouldQueue
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
    public function handle( CustomerService $customerService )
    {
        $this->order->load( 'customer' );

        $customerService->updateCustomerOwedAmount( $this->order->customer );
        $customerService->computeReward( $this->order );
        $customerService->increaseCustomerPurchase( $this->order );
    }
}
