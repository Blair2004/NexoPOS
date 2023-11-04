<?php

namespace App\Jobs;

use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
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
    public function __construct( public $products )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle( ReportService $reportService ): void
    {
        $this->products->each( function( $product ) use ( $reportService ) {
            // retreive the unit quantity for this product
            $unitQuantities =   ProductUnitQuantity::where( 'product_id', $product->id )
                ->get();

            $unitQuantities->each( function( $unitQuantity ) use ( $reportService, $product ) {
                $lastProductHistory     =   ProductHistory::where( 'product_id', $product->id )
                    ->where( 'unit_id', $unitQuantity->unit_id )
                    ->orderBy( 'id', 'desc' )
                    ->first();

                /**
                 * When we use the last product history, we need to use "after_quantity" as the "before_quantity"
                 * mainly because it should be the "after_quantity" for the next product history. 
                 * This change will not be persisted to the database for the last product history.
                 */
                $lastProductHistory->before_quantity   =   $lastProductHistory->after_quantity;

                $reportService->prepareProductHistoryCombinedHistory( $lastProductHistory )
                    ->save();
            });
        });
    }
}
