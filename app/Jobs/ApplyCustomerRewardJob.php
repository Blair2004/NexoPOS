<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\CustomerReward;
use App\Models\RewardSystem;
use App\Services\CustomerService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ApplyCustomerRewardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NsSerialize;

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
