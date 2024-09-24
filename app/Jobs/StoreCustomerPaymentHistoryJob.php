<?php

namespace App\Jobs;

use App\Models\CustomerAccountHistory;
use App\Models\OrderPayment;
use App\Services\CustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreCustomerPaymentHistoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct( public OrderPayment $payment )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle( CustomerService $customerService ): void
    {
        /**
         * When the customer is making some payment
         * we store it on his history.
         */
        if ( $this->payment->identifier === OrderPayment::PAYMENT_ACCOUNT ) {
            $customerService->saveTransaction(
                $this->payment->order->customer,
                CustomerAccountHistory::OPERATION_PAYMENT,
                $this->payment->value,
                __( 'Order Payment' ), [
                    'order_id' => $this->payment->order->id,
                ]
            );
        }
    }
}
