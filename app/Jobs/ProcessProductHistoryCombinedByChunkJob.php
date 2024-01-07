<?php

namespace App\Jobs;

use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessProductHistoryCombinedByChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public $products)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ReportService $reportService): void
    {
        $this->products->each(function ($product) use ($reportService) {
            // retreive the unit quantity for this product
            $unitQuantities = ProductUnitQuantity::where('product_id', $product->id)
                ->get();

            $unitQuantities->each(function ($unitQuantity) use ($reportService, $product) {
                $lastProductHistory = ProductHistory::where('product_id', $product->id)
                    ->where('unit_id', $unitQuantity->unit_id)
                    ->orderBy('id', 'desc')
                    ->first();

                if ($lastProductHistory instanceof ProductHistory) {
                    $reportService->prepareProductHistoryCombinedHistory($lastProductHistory)
                        ->save();
                }
            });
        });
    }
}
