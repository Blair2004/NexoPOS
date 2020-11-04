<?php

namespace App\Jobs;

use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderBeforeDeleteEvent;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComputeCashierSalesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $event;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( $event )
    {
        $this->event    =   $event;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order      =   $this->event->order;

        if ( $this->event instanceof OrderAfterCreatedEvent ) {
            if ( $order->payment_status === Order::PAYMENT_PAID ) {
                $order->author->total_sales         =   $order->author->total_sales + $order->total;
                $order->author->total_sale_count    =   $order->author->total_sale_count + 1;
                $order->author->save();
            }
        } else if ( $this->event instanceof OrderBeforeDeleteEvent ) {
            if ( $order->payment_status === Order::PAYMENT_PAID ) {
                $order->author->total_sales         =   $order->author->total_sales - $order->total;
                $order->author->total_sale_count    =   $order->author->total_sale_count - 1;
                $order->author->save();
            }
        }
    }
}
