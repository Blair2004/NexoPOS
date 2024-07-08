<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\DateService;
use App\Traits\NsSerialize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

class EnsureCombinedProductHistoryExistsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NsSerialize, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct( public $date = null )
    {
        $this->prepareSerialization();
    }

    /**
     * Execute the job.
     */
    public function handle( DateService $dateService ): void
    {
        if ( $this->date !== null ) {
            $now = $dateService->parse( $this->date )->startOfDay()->format( 'Y-m-d' );
        } else {
            $now = $dateService->startOfDay()->format( 'Y-m-d' );
        }

        // retrieve products with stock enabled by chunk of 20 and dispatch the job ProcessProductHistoryCombinedByChunkJob
        $delay = 10;
        Product::withStockEnabled()->chunk( 20, function ( $products ) use ( &$delay, $now ) {
            ProcessProductHistoryCombinedByChunkJob::dispatch( $products, $now )->delay( now()->addSeconds( $delay ) );
            $delay += 10;
        } );
    }
}
