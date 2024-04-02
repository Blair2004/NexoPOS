<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderRefund;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordTransactionForShippingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct( public Order $order, public OrderRefund $orderRefund )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle( TransactionService $transactionService ): void
    {
        $transactionService->createTransactionFormRefundedOrderShipping(
            order: $this->order,
            orderRefund: $this->orderRefund
        );
    }
}
