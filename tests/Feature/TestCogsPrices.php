<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductUnitQuantity;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use App\Services\TaxService;
use App\Services\TestService;
use Faker\Factory;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithProcurementTest;
use Tests\Traits\WithProductTest;

class TestCogsPrices extends TestCase
{
    use WithAuthentication, WithProcurementTest, WithProductTest;

    /**
     * A basic feature test example.
     */
    public function test_manual_cogs(): void
    {
        $this->attemptAuthenticate();

        $faker = Factory::create();
        $unitGroup = UnitGroup::first();
        $category = ProductCategory::first();
        $sale_price = 100;

        $response = $this->attemptSetProduct(
            skip_tests: true,
            form: [
                'name' => 'Fake Product',
                'variations' => [
                    [
                        '$primary' => true,
                        'expiracy' => [
                            'expires' => 0,
                            'on_expiration' => 'prevent_sales',
                        ],
                        'identification' => [
                            'barcode' => $faker->ean13(),
                            'barcode_type' => 'ean13',
                            'searchable' => $faker->randomElement( [ true, false ] ),
                            'category_id' => $category->id,
                            'description' => __( 'Created via tests' ),
                            'product_type' => 'product',
                            'type' => $faker->randomElement( [ Product::TYPE_MATERIALIZED, Product::TYPE_DEMATERIALIZED ] ),
                            'sku' => Str::random( 15 ) . '-sku',
                            'status' => 'available',
                            'stock_management' => 'enabled',
                        ],
                        'images' => [],
                        'taxes' => [
                            'tax_group_id' => TaxGroup::first()?->id ?: 0,
                            'tax_type' => 'inclusive',
                        ],
                        'units' => [
                            'selling_group' => $unitGroup->units()->limit( 1 )->get()->map( function ( $unit ) use ( $faker, $sale_price ) {
                                return [
                                    'sale_price_edit' => $sale_price,
                                    'wholesale_price_edit' => $faker->numberBetween( 20, 25 ),
                                    'cogs' => 10,
                                    'unit_id' => $unit->id,
                                ];
                            } )->toArray(),
                            'unit_group' => $unitGroup->id,
                        ],
                    ],
                ],
            ] );

        $product = $response[ 'data' ][ 'product' ];

        $this->assertTrue( (float) $product[ 'unit_quantities' ][0][ 'cogs' ] === (float) 10, 'The COGS price is not as manually defined' );
    }

    public function test_automatic_cogs(): void
    {
        $this->attemptAuthenticate();

        /**
         * @var TestService $testService
         */
        $testService = app()->make( TestService::class );
        /**
         * @var TaxService $taxService
         */
        $taxService = app()->make( TaxService::class );
        $faker = Factory::create();
        $unitGroup = UnitGroup::first();
        $category = ProductCategory::first();
        $sale_price = 100;
        $taxType = 'inclusive';
        $taxGroup = TaxGroup::first();
        $margin = 20; // %
        $form = [
            'name' => 'New Fake Product',
            'variations' => [
                [
                    '$primary' => true,
                    'expiracy' => [
                        'expires' => 0,
                        'on_expiration' => 'prevent_sales',
                    ],
                    'identification' => [
                        'barcode' => $faker->ean13(),
                        'barcode_type' => 'ean13',
                        'searchable' => $faker->randomElement( [ true, false ] ),
                        'category_id' => $category->id,
                        'description' => __( 'Created via tests' ),
                        'product_type' => 'product',
                        'type' => $faker->randomElement( [ Product::TYPE_MATERIALIZED, Product::TYPE_DEMATERIALIZED ] ),
                        'sku' => Str::random( 15 ) . '-sku',
                        'status' => 'available',
                        'stock_management' => 'enabled',
                    ],
                    'images' => [],
                    'taxes' => [
                        'tax_group_id' => TaxGroup::first()?->id ?: 0,
                        'tax_type' => 'inclusive',
                    ],
                    'units' => [
                        'selling_group' => $unitGroup->units()->limit( 1 )->get()->map( function ( $unit ) use ( $faker, $sale_price ) {
                            return [
                                'sale_price_edit' => $sale_price,
                                'wholesale_price_edit' => $faker->numberBetween( 20, 25 ),
                                'unit_id' => $unit->id,
                            ];
                        } )->toArray(),
                        'auto_cogs' => true,
                        'unit_group' => $unitGroup->id,
                    ],
                ],
            ],
        ];

        $response = $this->attemptSetProduct( form: $form, skip_tests: true );

        $product = $response[ 'data' ][ 'product' ];

        $this->assertTrue( $product[ 'auto_cogs' ], 'The auto COGS feature is not set to true while it should be.' );

        /**
         * Stept 2: We'll here try to make a procurement
         * and see how the cogs is updated for that particular product
         */
        $details = $testService->prepareProcurement( ns()->date->now(), [
            'products' => Product::where( 'id', $product[ 'id' ] )->get()->map( function ( Product $product ) {
                return $product->unitGroup->units->map( function ( $unit ) use ( $product ) {
                    // we retreive the unit quantity only if that is included on the group units.
                    $unitQuantity = $product->unit_quantities->filter( fn( $q ) => (int) $q->unit_id === (int) $unit->id )->first();

                    if ( $unitQuantity instanceof ProductUnitQuantity ) {
                        return (object) [
                            'unit' => $unit,
                            'unitQuantity' => $unitQuantity,
                            'product' => $product,
                        ];
                    }

                    return false;
                } )->filter();
            } )->flatten()->map( function ( $data ) use ( $taxService, $taxType, $taxGroup, $margin, $faker ) {
                $quantity = $faker->numberBetween( 100, 999 );

                return [
                    'product_id' => $data->product->id,
                    'gross_purchase_price' => 15,
                    'net_purchase_price' => 16,
                    'purchase_price' => $taxService->getTaxGroupComputedValue(
                        $taxType,
                        $taxGroup,
                        $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                            $data->unitQuantity->sale_price,
                            $margin
                        )
                    ),
                    'quantity' => $quantity,
                    'tax_group_id' => $taxGroup->id,
                    'tax_type' => $taxType,
                    'tax_value' => $taxService->getTaxGroupVatValue(
                        $taxType,
                        $taxGroup,
                        $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                            $data->unitQuantity->sale_price,
                            $margin
                        )
                    ),
                    'total_purchase_price' => $taxService->getTaxGroupComputedValue(
                        $taxType,
                        $taxGroup,
                        $data->unitQuantity->sale_price - $taxService->getPercentageOf(
                            $data->unitQuantity->sale_price,
                            $margin
                        )
                    ) * $quantity,
                    'unit_id' => $data->unit->id,
                ];
            } ),
        ] );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/procurements', $details );

        $productUnitQuantity = ProductUnitQuantity::where( 'unit_id', $product[ 'unit_quantities' ][0][ 'unit_id' ] )
            ->where( 'product_id', $product[ 'id' ] )
            ->first();

        $procuredProduct = $response[ 'data' ][ 'products' ][0];
        $cogs = ns()->currency->define( $procuredProduct[ 'total_purchase_price' ] )->dividedBy( $procuredProduct[ 'quantity' ] )->toFloat();

        $this->assertSame( (float) $productUnitQuantity->cogs, (float) $cogs, 'The automatically computed cogs is not accurate' );
    }
}
