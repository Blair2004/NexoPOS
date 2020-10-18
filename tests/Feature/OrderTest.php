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

        // $product    =   Product::withStockDisabled()->firstOrfail();
        $product    =   Product::find(1);
        $unit       =   $product->unit_quantities()->where( 'quantity', '>', 0 )->first();
        $subtotal   =   $unit->sale_price * 5;

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id'           =>  1,
                'type'                  =>  [ 'identifier' => 'takeaway' ],
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
                'subtotal'              =>  $subtotal,
                'shipping'              =>  150,
                'products'              =>  [
                    [
                        'product_id'            =>  $product->id,
                        'quantity'              =>  5,
                        'unit_price'            =>  12,
                        'unit_quantity_id'      =>  $unit->id,
                    ]
                ],
                'payments'              =>  [
                    [
                        'identifier'    =>  'cash-payment',
                        'amount'        =>  60 + 150
                    ]
                ]
            ]);
        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $subtotal   =   $subtotal - ( ( 2.5 * $subtotal ) / 100 );

        $response->assertJsonPath(
            'data.order.total', $subtotal + 150,
            'data.order.change', ( 60 + 150 ) - ( $subtotal + 150 ),
        );
    }
}
