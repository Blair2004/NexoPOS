<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Role;
use App\Models\TaxGroup;
use App\Models\UnitGroup;
use App\Services\CurrencyService;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Str;
use Tests\TestCase;

class CreateProductTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

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
        $sale_price     =   $faker->numberBetween(25,30);
        $categories     =   ProductCategory::where( 'parent_id', '>', 0 )
            ->get()
            ->map( fn( $cat ) => $cat->id );

        for( $i = 0; $i < 1000; $i++ ) {
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

            if ( $taxType === 'exclusive' ) {
                $response->assertJsonPath( 'data.product.unit_quantities.0.sale_price', $taxService->getTaxGroupComputedValue( $taxType, TaxGroup::find(1), $sale_price ) );
                $response->assertJsonPath( 'data.product.unit_quantities.0.incl_tax_sale_price', $taxService->getTaxGroupComputedValue( $taxType, TaxGroup::find(1), $sale_price ) );
                $response->assertJsonPath( 'data.product.unit_quantities.0.excl_tax_sale_price', $sale_price );
            } else {
                $response->assertJsonPath( 'data.product.unit_quantities.0.sale_price', $sale_price );
                $response->assertJsonPath( 'data.product.unit_quantities.0.incl_tax_sale_price', $taxService->getTaxGroupComputedValue( $taxType, TaxGroup::find(1), $sale_price ) );
                $response->assertJsonPath( 'data.product.unit_quantities.0.excl_tax_sale_price', $sale_price );
            }

            $response->assertStatus(200);
        }
    }
}
