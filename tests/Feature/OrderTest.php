<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Product;
use Tests\TestCase;

class OrderTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder()
    {
        $this->json( 'POST', 'auth/sign-in', [
            'username'  =>  env( 'TEST_USERNAME' ),
            'password'  =>  env( 'TEST_PASSWORD' )
        ]);

        $product    =   Product::withStockEnabled()->firstOrfail();

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id'           =>  1,
                'type'                  =>  [ 'identifier' => 'cash' ],
                'discount_type'         =>  'percentage',
                'discount_percentage'   =>  2.5,
                'addresses'             =>  [
                    'shipping'          =>  [
                        'name'          =>  'First Name Delivery',
                        'surname'       =>  'Surname',
                        'country'       =>  'Cameroon',
                    ],
                    'billing'          =>  [
                        'name'          =>  'EBENE Voundi',
                        'surname'       =>  'Antony Hervé',
                        'country'       =>  'United State Seattle',
                    ]
                ],
                'shipping'              =>  150,
                'products'              =>  [
                    [
                        'product_id'    =>  $product->id,
                        'quantity'      =>  5,
                        'sale_price'    =>  12,
                        'unit_id'       =>  json_decode( $product->selling_unit_ids )[0],
                    ]
                ],
                'payments'              =>  [
                    [
                        'identifier'    =>  'cash',
                        'amount'        =>  60 + 150
                    ]
                ]
            ]);
        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $response->assertJsonPath(
            'data.order.total', 58.5 + 150,
            'data.order.change', ( 60 + 150 ) - 58.5,
        );
    }
}
