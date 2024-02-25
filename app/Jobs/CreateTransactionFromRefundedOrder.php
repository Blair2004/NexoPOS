<?php

namespace App\Jobs;

use App\Models\OrderRefund;
use App\Services\TransactionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateTransactionFromRefundedOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct( public OrderRefund $orderRefund )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(
        TransactionService $transactionService
    ): void {
        if ( $this->orderRefund->shipping > 0 ) {
            $transactionService->createTransactionHistory(
                value: $this->orderRefund->shipping,
                name: 'Refunded Shipping Fees',
                order_id: $this->orderRefund->order->id,
                order_refund_id: $this->orderRefund->id,
                operation: 'debit',
                transaction_account_id: ns()->option->get( 'ns_sales_refunds_account' ),
            );
        }
    }
}
