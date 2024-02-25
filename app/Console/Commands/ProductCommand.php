<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductGallery;
use App\Models\ProductSubItem;
use App\Models\ProductUnitQuantity;
use App\Services\ProductService;
use App\Services\TaxService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Modules\NsMultiStore\Models\Store;

class ProductCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ns:products {action} {--where=*} {--orWhere=*} {--store=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform various operations on the products';

    protected ProductService $productService;

    public function __construct()
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
        /**
         * explicit support for multistore
         */
        if ( class_exists( Store::class ) && ! empty( $this->option( 'store' ) ) ) {
            ns()->store->setStore( Store::find( $this->option( 'store' ) ) );
        }

        /**
         * @var ProductService
         */
        $this->productService = app()->make( ProductService::class );

        match ( $this->argument( 'action' ) ) {
            'update' => $this->updateProducts(),
            'compute-taxes' => $this->computeTaxes(),
            'refresh-barcode' => $this->refreshBarcodes()
        };
    }

    private function computeTaxes()
    {
        /**
         * @var TaxService
         */
        $taxService = app()->make( TaxService::class );

        $this->withProgressBar( ProductUnitQuantity::with( 'product.tax_group' )->get(), function ( ProductUnitQuantity $productUnitQuantity ) use ( $taxService ) {
            $taxService->computeTax(
                product: $productUnitQuantity,
                tax_group_id: $productUnitQuantity->product->tax_group_id,
                tax_type: $productUnitQuantity->product->tax_type
            );
        } );

        $this->newLine();

        $this->info( __( 'The products taxes were computed successfully.' ) );
    }

    private function refreshBarcodes()
    {
        $queryBuilder = $this->queryBuilder();

        $products = $this->withProgressBar( $queryBuilder->get(), function ( $product ) {
            $this->productService->generateProductBarcode( $product );
        } );

        $this->newLine();

        return $this->info( __( 'The product barcodes has been refreshed successfully.' ) );
    }

    private function perform( Builder $queryBuilder, $callback )
    {
        $results = $queryBuilder->get();
        $this->withProgressBar( $results, fn( $entry ) => $callback( $entry ) );
        $this->newLine();
    }

    private function updateProducts()
    {
        $queryBuilder = $this->queryBuilder();

        $this->perform( $queryBuilder, function ( $product ) {
            $gallery = ProductGallery::where( 'product_id', $product->id )->get();
            $units = ProductUnitQuantity::where( 'product_id', $product->id )->get();
            $subItems = ProductSubItem::where( 'product_id', $product->id )->get();

            $this->productService->update( $product, array_merge( $product->toArray(), [
                'units' => [
                    'unit_group' => $product->unit_group,
                    'accurate_tracking' => $product->accurate_tracking,
                    'selling_group' => $units->map( fn( $unitQuantity ) => $unitQuantity->toArray() )->toArray(),
                ],
                'images' => $gallery->map( fn( $gallery ) => $gallery->toArray() )->toArray(),
                'groups' => [
                    'product_subitems' => $subItems->map( fn( $subItem ) => $subItem->toArray() )->toArray(),
                ],
            ] ) );
        } );

        $this->newLine();

        return $this->info( sprintf(
            __( '%s products where updated.' ),
            $queryBuilder->count()
        ) );
    }

    private function queryBuilder()
    {
        if ( ! empty( $this->option( 'where' ) ) || ! empty( $this->option( 'orWhere' ) ) ) {
            $query = ( new Product )->newQuery();

            foreach ( [ 'where', 'orWhere' ] as $option ) {
                if ( ! empty( $this->option( $option ) ) ) {
                    foreach ( $this->option( $option ) as $optionStatement ) {
                        $equalStatement = explode( ':', $optionStatement );
                        $greatherStatement = explode( '>', $optionStatement );
                        $lessThanStatement = explode( '<', $optionStatement );

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
