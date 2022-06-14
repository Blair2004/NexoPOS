<?php

namespace App\Console\Commands;

use App\Crud\ProductCrud;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ProductCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:products {action} {--where=*} {--orWhere=*}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform various operations on the products';

    public function __construct(
        protected ProductService $productService
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        match( $this->argument( 'action' ) ) {
            'update'            =>  $this->updateProducts(),
            'refresh-barcode'   =>  $this->refreshBarcodes()
        };
    }

    private function refreshBarcodes()
    {
        $queryBuilder   =   $this->queryBuilder();

        $products   =   $this->withProgressBar( $queryBuilder->get(), function( $product ) {
            $this->productService->generateProductBarcode( $product );
        });

        $this->newLine();

        return $this->info( __( 'The product barcodes has been refreshed successfully.' ) );
    }

    private function perform( Builder $queryBuilder, $callback )
    {
        $this->withProgressBar( $queryBuilder->get(), fn( $entry ) => $callback( $entry ) );
        $this->newLine();
    }

    private function updateProducts()
    {
        $queryBuilder   =   $this->queryBuilder();

        $this->perform( $queryBuilder, function( $product ) {
            $this->productService->update( $product, $product->toArray() );
        });

        $this->newLine();

        return $this->info( sprintf(
            __( '%s prodcuts where updated.' ), 
            $queryBuilder->count()
        ) );
    }

    private function queryBuilder()
    {
        if ( ! empty( $this->option( 'where' ) ) || ! empty( $this->option( 'orWhere' ) ) ) {
            $query  =   ( new Product )->newQuery();

            foreach([ 'where', 'orWhere' ] as $option ) {
                if ( ! empty( $this->option( $option ) ) ) {
                    foreach( $this->option( $option ) as $optionStatement ) {
                        $equalStatement     =   explode( ':', $optionStatement );
                        $greatherStatement  =   explode( '>', $optionStatement );
                        $lessThanStatement  =   explode( '<', $optionStatement );
    
                        if ( 
                            count( $equalStatement ) === 2 && 
                            count( $greatherStatement ) === 1 && 
                            count( $lessThanStatement ) === 1 ) {
                            $query->$option( $equalStatement[0], $equalStatement[1] );
                        }

                        if ( 
                            count( $greatherStatement ) === 2 && 
                            count( $equalStatement ) === 1 && 
                            count( $lessThanStatement ) === 1 ) {
                            $query->$option( $greatherStatement[0], '>', $greatherStatement[1] );
                        }

                        if ( 
                            count( $lessThanStatement ) === 2 && 
                            count( $equalStatement ) === 1 && 
                            count( $greatherStatement ) === 1 ) {
                            $query->$option( $lessThanStatement[0], '<', $lessThanStatement[1] );
                        }
                    }

                }
            }
            
            return $query;

        } else {
            return Product::where( 'id', '>', 0 );
        }
    }
}
