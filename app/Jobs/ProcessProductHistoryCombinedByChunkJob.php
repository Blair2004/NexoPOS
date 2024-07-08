<?php

namespace App\Jobs;

use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Services\ReportService;
use Carbon\Carbon;
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
    public function __construct( public $products, public $date )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle( ReportService $reportService ): void
    {
        $this->products->each( function ( $product ) use ( $reportService ) {
            // retreive the unit quantity for this product
            $unitQuantities = ProductUnitQuantity::where( 'product_id', $product->id )
                ->get();

            $unitQuantities->each( function ( $unitQuantity ) use ( $reportService, $product ) {
                $lastProductHistory = ProductHistory::where( 'product_id', $product->id )
                    ->where( 'unit_id', $unitQuantity->unit_id )
                    ->orderBy( 'id', 'desc' );

                if ( $this->date !== null ) {
                    /**
                     * $this->date is supposed to be the start of the day.
                     */
                    $lastProductHistory->whereDate( 'created_at', '>', Carbon::parse( $this->date )->toDateTimeString() );
                }

                $lastProductHistory = $lastProductHistory->first();

                if ( $lastProductHistory instanceof ProductHistory ) {
                    $reportService->prepareProductHistoryCombinedHistory( $lastProductHistory )->save();
                    $reportService->computeProductHistoryCombinedForWholeDay( $lastProductHistory );
                }
            } );
        } );
    }
}
