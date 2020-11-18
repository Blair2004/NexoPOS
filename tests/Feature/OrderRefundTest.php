<?php

namespace Tests\Feature;

use App\Models\OrderPayment;
use App\Models\OrderProductRefund;
use App\Models\Product;
use App\Models\Role;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderRefundTest extends TestCase
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

        $currency       =   app()->make( CurrencyService::class );
        $product        =   Product::withStockEnabled()->with( 'unit_quantities' )->first();
        $shippingFees   =   150;
        $discountRate   =   3.5;
        $products       =   [
            [
                'product_id'            =>  $product->id,
                'quantity'              =>  5,
                'unit_price'            =>  $product->unit_quantities[0]->sale_price,
                'unit_quantity_id'      =>  $product->unit_quantities[0]->id,
            ]
        ];

        $subtotal   =   collect( $products )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id'           =>  1,
                'type'                  =>  [ 'identifier' => 'takeaway' ],
                'discount_type'         =>  'percentage',
                'discount_percentage'   =>  $discountRate,
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
                'products'              =>  $products,
                'payments'              =>  [
                    [
                        'identifier'    =>  OrderPayment::PAYMENT_CASH,
                        'value'         =>  $subtotal
                    ]
                ]
            ]);
        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $responseData   =   json_decode( $response->getContent(), true );

        /**
         * We'll keep original products amounts and quantity
         * this means we're doing a full refund of price and quantities
         */
        $responseData[ 'data' ][ 'order' ][ 'products' ][0][ 'condition' ]      =   OrderProductRefund::CONDITION_DAMAGED;
        $responseData[ 'data' ][ 'order' ][ 'products' ][0][ 'quantity' ]       =   1;
        $responseData[ 'data' ][ 'order' ][ 'products' ][0][ 'description' ]    =   __( 'The product wasn\'t properly manufactured, causing external damage to the device during the shipment.' );

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $responseData[ 'data' ][ 'order' ][ 'id' ] . '/refund', [
                'payment'   =>  [
                    'identifier'    =>  'customer-account',
                ],
                'total'     =>  $responseData[ 'data' ][ 'order' ][ 'total' ],
                'products'  =>  $responseData[ 'data' ][ 'order' ][ 'products' ],
            ]);
        
        $response->assertJson([
            'status'    =>  'success'
        ]);
    }
}
