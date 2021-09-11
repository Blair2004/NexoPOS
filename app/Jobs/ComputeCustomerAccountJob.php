<?php

namespace App\Jobs;

use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterRefundedEvent;
use App\Events\OrderAfterUpdatedEvent;
use App\Events\OrderBeforeDeleteEvent;
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
     * @var CustomerService
     */
    protected $customerService;

    public $event;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        $event,
        CustomerService $customerService
    ) {
        $this->event            =   $event;
        $this->customerService  =   $customerService;
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
        } else if ($this->event instanceof OrderAfterRefundedEvent) {
            $this->reduceCustomerPurchases($this->event);
        } else if (
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
            $customerService        =       app()->make(CustomerService::class);

            $customerService->computeReward(
                $event->order,
                $event->order->customer
            );
        }
    }

    private function reduceCustomerPurchases(OrderAfterRefundedEvent $event)
    {
        $event->order->customer->purchases_amount     =  $event->order->customer->purchases_amount - $event->orderRefund->total;
        $event->order->customer->save();
    }

    private function handleDeletion(OrderBeforeDeleteEvent $event)
    {
        switch ($event->order->payment_status) {
            case 'paid':
                $event->order->customer->purchases_amount       -=  $event->order->total;
                break;
            case 'partially_paid':
                $event->order->customer->purchases_amount       -=  $event->order->tendered;
                break;
            default:
                $event->order->customer->owed_amount            -=  $event->order->total;
                break;
        }

        $event->order->customer->save();
    }
}
