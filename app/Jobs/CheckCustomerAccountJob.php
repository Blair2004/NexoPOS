<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\OrderPayment;
use App\Services\CustomerService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class CheckCustomerAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Customer $customer, public $payment )
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
        if ( $this->payment[ 'identifier' ] === OrderPayment::PAYMENT_ACCOUNT ) {
            $customerService->canReduceCustomerAccount( $this->customer, $this->payment[ 'value' ] );
        }
    }
}
