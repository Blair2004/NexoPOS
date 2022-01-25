<?php
namespace Tests\Traits;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductHistory;
use App\Models\ProductUnitQuantity;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use App\Services\CurrencyService;
use App\Services\TaxService;
use Exception;
use Illuminate\Support\Str;

trait WithProductTest
{
    protected function attemptCreateProduct()
    {
        /**
         * @var CurrencyService
         */
        $currency       =   app()->make( CurrencyService::class );

        $faker          =   \Faker\Factory::create();

        /**
         * @var TaxService
         */
        $taxService     =   app()->make( TaxService::class );
        $taxType        =   $faker->randomElement([ 'exclusive', 'inclusive' ]);
        $unitGroup      =   UnitGroup::first();
        $sale_price     =   $faker->numberBetween(5,10);
        $categories     =   ProductCategory::where( 'parent_id', '>', 0 )
            ->get()
            ->map( fn( $cat ) => $cat->id )
            ->toArray();

        for( $i = 0; $i < 30; $i++ ) {
            $response   = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', '/api/nexopos/v4/products/', [
                'name'          =>  $faker->word,
                'variations'    =>  [
                    [
                        '$primary'  =>  true,
                        'expiracy'  =>  [
                            'expires'       =>  0,
                            'on_expiration' =>  'prevent_sales',
                        ],
                        'identification'    =>  [
                            'barcode'           =>  $faker->ean13(),
                            'barcode_type'      =>  'ean13',
                            'searchable'        =>  $faker->randomElement([ true, false ]),
                            'category_id'       =>  $faker->randomElement( $categories ),
                            'description'       =>  __( 'Created via tests' ),
                            'product_type'      =>  'product',
                            'type'              =>  $faker->randomElement([ 'materialized', 'dematerialized' ]),
                            'sku'               =>  Str::random(15) . '-sku',
                            'status'            =>  'available',
                            'stock_management'  =>  'enabled',   
                        ],
                        'images'            =>  [],
                        'taxes'             =>  [
                            'tax_group_id'  =>  1,
                            'tax_type'      =>  $taxType,
                        ],
                        'units'             =>  [
                            'selling_group' =>  $unitGroup->units->map( function( $unit ) use ( $faker, $sale_price ) {
                                return [
                                    'sale_price_edit'       =>  $sale_price,
                                    'wholesale_price_edit'  =>  $faker->numberBetween(20,25),
                                    'unit_id'               =>  $unit->id
                                ];
                            }),
                            'unit_group'    =>  $unitGroup->id
                        ]
                    ]
                ]
            ]);

            $result     =   json_decode( $response->getContent(), true );

            if ( $taxType === 'exclusive' ) {
                $this->assertEquals( ( float ) data_get( $result, 'data.product.unit_quantities.0.sale_price' ), $taxService->getTaxGroupComputedValue( $taxType, TaxGroup::find(1), $sale_price ) );
                $this->assertEquals( ( float ) data_get( $result, 'data.product.unit_quantities.0.incl_tax_sale_price' ), $taxService->getTaxGroupComputedValue( $taxType, TaxGroup::find(1), $sale_price ) );
                $this->assertEquals( ( float ) data_get( $result, 'data.product.unit_quantities.0.excl_tax_sale_price' ), $sale_price );
            } else {
                $this->assertEquals( ( float ) data_get( $result, 'data.product.unit_quantities.0.sale_price', 0 ), $sale_price );
                $this->assertEquals( ( float ) data_get( $result, 'data.product.unit_quantities.0.incl_tax_sale_price', 0 ), $taxService->getTaxGroupComputedValue( $taxType, TaxGroup::find(1), $sale_price ) );
                $this->assertEquals( ( float ) data_get( $result, 'data.product.unit_quantities.0.excl_tax_sale_price', 0 ), $sale_price );
            }

            $response->assertStatus(200);
        }
    }

    protected function attemptAdjustmentByDeletion()
    {
        $product            =   Product::find(1);
        $unitQuantity       =   $product->unit_quantities[0];

        $response           =   $this->json( 'POST', '/api/nexopos/v4/products/adjustments', [
            'products'              =>  [
                [
                    'id'                =>  $product->id,
                    'adjust_action'     =>  'deleted',
                    'name'              =>  $product->name,
                    'adjust_unit'       =>  $unitQuantity,
                    'adjust_reason'     =>  __( 'Performing a test adjustment' ),
                    'adjust_quantity'   =>  1
                ]
            ]
        ]);

        $response->assertJsonPath( 'status', 'success' );
    }

    protected function attemptTestSearchable()
    {
        $searchable     =   Product::searchable()->first();

        if ( $searchable instanceof Product ) {
            $response   = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', '/api/nexopos/v4/categories/pos/' . $searchable->category_id );
            
            $response   =   json_decode( $response->getContent(), true );
            $exists     =   collect( $response[ 'products' ] )
                ->filter( fn( $product ) => ( int ) $product[ 'id' ] === ( int ) $searchable->id )
                ->count() > 0;

            return $this->assertTrue( $exists, __( 'Searchable product cannot be found on category.' ) );
        }

        return $this->assertTrue( true );
    }

    protected function attemptNotSearchableAreSearchable()
    {
        $searchable     =   Product::searchable( false )->first();

        if ( $searchable instanceof Product ) {
            $response   = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'GET', '/api/nexopos/v4/categories/pos/' . $searchable->category_id );
            
            $response   =   json_decode( $response->getContent(), true );
            $exists     =   collect( $response[ 'products' ] )
                ->filter( fn( $product ) => ( int ) $product[ 'id' ] === ( int ) $searchable->id )
                ->count() === 0;

            return $this->assertTrue( $exists, __( 'Not searchable product cannot be found on category.' ) );
        }

        return $this->assertTrue( true );
    }

    protected function attemptDecreaseStockCount()
    {
        $productQuantity    =   ProductUnitQuantity::where( 'quantity', '>', 0 )->first();
        
        if ( ! $productQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( __( 'Unable to find a product to perform this test.' ) );
        }

        $product    =   $productQuantity->product;

        foreach( ProductHistory::STOCK_REDUCE as $action ) {
            $response       =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/products/adjustments', [
                    'products'  =>  [
                        [
                            'adjust_action'     =>  $action,
                            'adjust_unit'       =>  [
                                'sale_price'    =>  $productQuantity->sale_price,
                                'unit_id'       =>  $productQuantity->unit_id,
                            ],
                            'id'                =>  $product->id,
                            'adjust_quantity'   =>  1,
                        ]
                    ]
                ]);

            $oldQuantity    =   $productQuantity->quantity;
            $productQuantity->refresh();

            $response->assertStatus(200);

            $this->assertTrue( 
                $oldQuantity - $productQuantity->quantity === ( float ) 1, 
                sprintf(
                    __( 'The stock modification : %s hasn\'t made any change' ),
                    $action
                )
            );
        }
    }

    protected function attemptProductStockAdjustment()
    {
        $productQuantity    =   ProductUnitQuantity::where( 'quantity', '>', 0 )->first();
        
        if ( ! $productQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( __( 'Unable to find a product to perform this test.' ) );
        }

        $product    =   $productQuantity->product;

        foreach( ProductHistory::STOCK_INCREASE as $action ) {
            $response       =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/products/adjustments', [
                    'products'  =>  [
                        [
                            'adjust_action'     =>  $action,
                            'adjust_unit'       =>  [
                                'sale_price'    =>  $productQuantity->sale_price,
                                'unit_id'       =>  $productQuantity->unit_id,
                            ],
                            'id'                =>  $product->id,
                            'adjust_quantity'   =>  10,
                        ]
                    ]
                ]);

            $oldQuantity    =   $productQuantity->quantity;
            $productQuantity->refresh();

            $response->assertStatus(200);

            $this->assertTrue( 
                $productQuantity->quantity - $oldQuantity === ( float ) 10, 
                sprintf(
                    __( 'The stock modification : %s hasn\'t made any change' ),
                    $action
                )
            );
        }
    }
}