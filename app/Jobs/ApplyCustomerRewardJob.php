<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\RewardSystem;
use App\Services\CustomerService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ApplyCustomerRewardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Customer $customer, public CustomerReward $customerReward, public RewardSystem $reward )
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
        $customerService->applyReward(
            customer: $this->customer,
            customerReward: $this->customerReward,
            reward: $this->reward
        );
    }
}
