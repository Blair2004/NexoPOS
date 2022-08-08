<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\OrderPayment;
use App\Services\CustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckCustomerAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Customer $customer, public $payment )
    {
        //
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
