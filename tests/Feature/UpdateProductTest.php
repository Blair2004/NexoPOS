<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Models\UnitGroup;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateProductTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateProduct()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $product = Product::get()->random()->first();

        $response = $this
            ->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', '/api/products/' . $product->id, [
                'name' => 'Sample Product',
                'variations' => [
                    [
                        '$primary' => true,
                        'expiracy' => [
                            'expires' => 0,
                            'on_expiration' => 'prevent_sales',
                        ],
                        'identification' => [
                            'barcode' => 'quassas',
                            'barcode_type' => 'ean13',
                            'category_id' => 1,
                            'description' => __( 'Created via tests' ),
                            'product_type' => 'product',
                            'sku' => 'sample-sku',
                            'status' => 'available',
                            'stock_management' => 'enabled',
                        ],
                        'images' => [],
                        'taxes' => [
                            'tax_group_id' => 1,
                            'tax_type' => 'exclusive',
                        ],
                        'units' => [
                            'selling_group' => [
                                [
                                    'sale_price' => 10,
                                    'wholesale_price' => 9.55,
                                    'unit_id' => UnitGroup::find( 2 )->units->random()->first()->id,
                                ],
                            ],
                            'unit_group' => 2,
                        ],
                    ],
                ],
            ] );

        $response->assertStatus( 200 );
    }
}
