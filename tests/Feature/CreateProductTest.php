<?php

namespace Tests\Feature;

use App\Models\Product;
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

        /**
         * @var TaxService
         */
        $taxService     =   app()->make( TaxService::class );
        $taxType        =   'exclusive';

        $response   = $this
            ->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', '/api/nexopos/v4/products/', [
            'name'          =>  'Sample Product',
            'variations'    =>  [
                [
                    '$primary'  =>  true,
                    'expiracy'  =>  [
                        'expires'       =>  0,
                        'on_expiration' =>  'prevent_sales',
                    ],
                    'identification'    =>  [
                        'barcode'           =>  Str::random(10),
                        'barcode_type'      =>  'ean13',
                        'category_id'       =>  1,
                        'description'       =>  __( 'Created via tests' ),
                        'product_type'      =>  'product',
                        'sku'               =>  Str::random(5) . '-sku',
                        'status'            =>  'available',
                        'stock_management'  =>  'enabled',   
                    ],
                    'images'            =>  [],
                    'taxes'             =>  [
                        'tax_group_id'  =>  1,
                        'tax_type'      =>  $taxType,
                    ],
                    'units'             =>  [
                        'selling_group' =>  [
                            [
                                'sale_price_edit'       =>  10,
                                'wholesale_price_edit'  =>  9.5,
                                'unit_id'               =>  UnitGroup::find(2)->units->random()->first()->id
                            ]
                        ],
                        'unit_group'    =>  2
                    ]
                ]
            ]
        ]);

        $response->dump();
        $response->assertJsonPath( 'data.product.unit_quantities.0.sale_price', $taxService->getTaxGroupComputedValue( $taxType, TaxGroup::find(1), 10 ) );
        $response->assertStatus(200);
    }
}
