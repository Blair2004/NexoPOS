<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderProductRefund;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateExpenseFromRefundJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public Order $order, public OrderProductRefund $orderProductRefund, public OrderProduct $orderProduct )
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( TransactionService $transactionService )
    {
        /**
         * @todo Implement this job
         */
    }
}
