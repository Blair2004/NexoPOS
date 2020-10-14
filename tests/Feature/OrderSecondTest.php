<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderSecondTest extends TestCase
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

        $product    =   Product::find(10);

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id'           =>  30,
                'type'                  =>  [ 'identifier' => 'takeaway' ],
                'discount_type'         =>  null,
                'discount_percentage'   =>  0,
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
                'shipping'              =>  0,
                'products'              =>  [
                    [
                        'product_id'    =>  $product->id,
                        'quantity'      =>  8,
                        'sale_price'    =>  41,
                        'unit_id'       =>  1,
                    ], [
                        'product_id'    =>  3,
                        'quantity'      =>  1,
                        'sale_price'    =>  44,
                        'unit_id'       =>  5,
                    ], [
                        'product_id'    =>  1,
                        'quantity'      =>  5,
                        'sale_price'    =>  150,
                        'unit_id'       =>  6,
                    ]
                ],
                'payments'              =>  [
                    [
                        'identifier'    =>  'cash-payment',
                        'amount'        =>  1500
                    ]
                ]
            ]);
        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $response->assertJsonPath(
            'data.order.total', 1122,
            'data.order.change', 378,
        );
    }
}
