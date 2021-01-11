<?php

namespace App\Jobs;

use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterRefundedEvent;
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
                $order->user->total_sales           =   $order->user->total_sales + $order->total;
                $order->user->total_sales_count     =   $order->user->total_sales_count + 1;
                $order->user->save();
            }
        } else if ( $this->event instanceof OrderBeforeDeleteEvent ) {
            if ( $order->payment_status === Order::PAYMENT_PAID ) {
                $order->user->total_sales           =   $order->user->total_sales - $order->total;
                $order->user->total_sales_count     =   $order->user->total_sales_count - 1;
                $order->user->save();
            }
        } else if ( $this->event instanceof OrderAfterRefundedEvent ) {
            $order->user->total_sales           -=  $this->event->orderRefund->total;
            $order->user->save();
        }
    }
}
