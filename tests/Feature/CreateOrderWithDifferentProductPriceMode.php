<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Role;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateOrderWithDifferentProductPriceMode extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $currency       =   app()->make( CurrencyService::class );
        $product        =   Product::withStockEnabled()->get()->random();
        $unit           =   $product->unit_quantities()->where( 'quantity', '>', 0 )->first();
        $subtotal       =   $unit->sale_price * 5;
        $shippingFees   =   150;

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
                'shipping'              =>  $shippingFees,
                'products'              =>  [
                    [
                        'product_id'            =>  $product->id,
                        'quantity'              =>  1,
                        'unit_price'            =>  8.5,
                        'unit_quantity_id'      =>  $unit->id,
                        'mode'                  =>  'retail'
                    ], [
                        'product_id'            =>  $product->id,
                        'quantity'              =>  1,
                        'unit_price'            =>  8.5,
                        'unit_quantity_id'      =>  $unit->id,
                        'mode'                  =>  'normal'
                    ]
                ],
                'payments'              =>  [
                    [
                        'identifier'    =>  'paypal-payment',
                        'value'         =>  $currency->define( $subtotal )
                            ->additionateBy( $shippingFees )
                            ->getRaw()
                    ]
                ]
            ]);

        $response   =   json_decode( $response->getContent(), true );
        $order      =   $response[ 'data' ][ 'order' ];

        $this->assertTrue( $order[ 'products' ][0][ 'mode' ] === 'retail', 'Failed to assert the first product price mode is "retail"' );
        $this->assertTrue( $order[ 'products' ][1][ 'mode' ] === 'normal', 'Failed to assert the second product price mode is "normal"' );
    }
}
