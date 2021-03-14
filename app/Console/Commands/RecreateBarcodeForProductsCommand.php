<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BarcodeService;
use App\Services\ProductService;
use App\Models\Product;

class RecreateBarcodeForProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:products {operation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform various bulk operations on products';

    /**
     * The barcode service used to generate barcode
     * 
     * @var ProductService $barcodeService
     */
    protected $productService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        ProductService $productService
    )
    {
        parent::__construct();

        $this->productService   =   $productService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ( $this->argument( 'operation' ) === 'refresh-barcode' ) {
            $products   =   $this->withProgressBar( Product::get(), function( $product ) {
                $this->productService->generateProductBarcode( $product );
            });

            $this->newLine();
            return $this->info( __( 'The product barcodes has been refreshed successfully.' ) );
        }

        return $this->error( __( 'Invalid operation provided.' ) );
    }
}
