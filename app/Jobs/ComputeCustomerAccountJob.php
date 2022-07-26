<?php

namespace App\Jobs;

use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterUpdatedEvent;
use App\Events\OrderBeforeDeleteEvent;
use App\Models\Customer;
use App\Models\Order;
use App\Services\CustomerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComputeCustomerAccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        public $event,
        protected CustomerService $customerService
    ) {
        // ...
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->event instanceof OrderBeforeDeleteEvent) {
            $this->handleDeletion($this->event);
        } elseif (
            $this->event instanceof OrderAfterCreatedEvent ||
            $this->event instanceof OrderAfterUpdatedEvent
        ) {
            $this->computeCustomerOwed($this->event);
            $this->computeCustomerRewards($this->event);
        }
    }

    /**
     * We'll make sure to update the customer owed amount
     * when even he's involved on a transaction.
     */
    private function computeCustomerOwed( $event )
    {
        $this->customerService->updateCustomerOwedAmount($event->order->customer);
    }

    private function computeCustomerRewards( $event )
    {
        if ($event->order->payment_status === Order::PAYMENT_PAID) {
            /**
             * @var CustomerService
             */
            $customerService = app()->make(CustomerService::class);

            $customerService->computeReward(
                $event->order,
                $event->order->customer
            );
        }
    }

    private function handleDeletion(OrderBeforeDeleteEvent $event)
    {
        $customer = Customer::find( $event->order->customer_id );

        switch ($event->order->payment_status) {
            case 'paid':
                $customer->purchases_amount -= $event->order->total;
                break;
            case 'partially_paid':
                $customer->purchases_amount -= $event->order->tendered;
                break;
            default:
                $customer->owed_amount -= $event->order->total;
                break;
        }

        $customer->save();
    }
}
