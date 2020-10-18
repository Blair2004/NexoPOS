<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CreateProductTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCreateProduct()
    {
        $this->json( 'POST', 'auth/sign-in', [
            'username'  =>  env( 'TEST_USERNAME' ),
            'password'  =>  env( 'TEST_PASSWORD' )
        ]);

        $response = $this
            ->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', '/api/nexopos/v4/products/1', [
            'name'          =>  'Sample Product',
            'variations'    =>  [
                [
                    '$primary'  =>  true,
                    'expiracy'  =>  [
                        'expires'       =>  0,
                        'on_expiration' =>  'prevent_sales',
                    ],
                    'identification'    =>  [
                        'barcode'           =>  'quassas',
                        'barcode_type'      =>  'ean13',
                        'category_id'       =>  1,
                        'description'       =>  __( 'Created via tests' ),
                        'product_type'      =>  'product',
                        'sku'               =>  'sample-sku',
                        'status'            =>  'available',
                        'stock_management'  =>  'enabled',   
                    ],
                    'images'            =>  [],
                    'taxes'             =>  [
                        'tax_group_id'  =>  1,
                        'tax_type'      =>  'exclusive',
                    ],
                    'units'             =>  [
                        'selling_group' =>  [
                            [
                                'sale_price'        =>  10,
                                'wholesale_price'   =>  9.55,
                                'unit'              =>  6
                            ]
                        ],
                        'unit_group'    =>  2
                    ]
                ]
            ]
        ]);

        $response->dump();

        $response->assertStatus(200);
    }
}
