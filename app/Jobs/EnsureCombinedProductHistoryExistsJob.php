<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductHistoryCombined;
use App\Services\DateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EnsureCombinedProductHistoryExistsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(DateService $dateService): void
    {
        $now = $dateService->now()->clone()->startOfDay()->format('Y-m-d');

        // retreive the first ProductHistory that was created during that day using $now
        $productHistoryCombined = ProductHistoryCombined::where('created_at', '>', $now)->first();

        if (! $productHistoryCombined instanceof ProductHistoryCombined) {
            // retrieve products with stock enabled by chunk of 20 and dispatch the job ProcessProductHistoryCombinedByChunkJob
            $delay = 10;
            Product::withStockEnabled()->chunk(20, function ($products) use (&$delay) {
                ProcessProductHistoryCombinedByChunkJob::dispatch($products)->delay(now()->addSeconds($delay));
                $delay += 10;
            });
        }
    }
}
