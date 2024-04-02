<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class UncountDeletedOrderForCustomerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public $order )
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $customer = Customer::find( $this->order->customer_id );

        switch ( $this->order->payment_status ) {
            case 'paid':
                $customer->purchases_amount -= $this->order->total;
                break;
            case 'partially_paid':
                $customer->purchases_amount -= $this->order->tendered;
                break;
            default:
                $customer->owed_amount -= $this->order->total;
                break;
        }

        $customer->save();
    }
}
