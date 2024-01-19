<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CashRegistersService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class RecordRegisterHistoryUsingPaymentStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(public Order $order, public string $previous, public string $new)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @todo check if this is still necessary
     *
     * @return void
     */
    public function handle(CashRegistersService $cashRegistersService)
    {
        $cashRegistersService->createRegisterHistoryUsingPaymentStatus(
            order: $this->order,
            previous: $this->previous,
            new: $this->new
        );
    }
}
