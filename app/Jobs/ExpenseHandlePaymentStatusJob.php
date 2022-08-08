<?php

namespace App\Jobs;

use App\Models\Expense;
use App\Models\Order;
use App\Services\ExpenseService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExpenseHandlePaymentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NsSerialize;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct( public string $previous, public string $new, public Order $order )
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle( ExpenseService $expenseService )
    {
        $expenseService->handlePaymentStatus(
            order: $this->order,
            new: $this->new,
            previous: $this->previous
        );
    }
}
