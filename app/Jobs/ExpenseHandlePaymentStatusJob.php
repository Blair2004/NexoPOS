<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\TransactionService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class ExpenseHandlePaymentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Order $order, public string $previous, public string $new)
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(TransactionService $transactionService)
    {
        $transactionService->handlePaymentStatus(
            order: $this->order,
            new: $this->new,
            previous: $this->previous
        );
    }
}
