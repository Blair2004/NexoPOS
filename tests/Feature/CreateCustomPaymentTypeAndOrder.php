<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\Role;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateCustomPaymentTypeAndOrder extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_create_payment_type_and_order()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        /**
         * To avoid any error, we'll make sure to delete the 
         * payment type if that already exists.
         */
        $paymentType    =   PaymentType::identifier( 'paypal-payment' )->first();

        if ( $paymentType instanceof PaymentType ) {
            $paymentType->delete();
        }

        /**
         * First we'll create a custom payment type.
         * that has paypal as identifier
         */
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.payments-types', [
                'label'         =>  __( 'PayPal' ),
                'general'       =>  [
                    'identifier'    =>  'paypal-payment',
                    'active'        =>  true
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        /**
         * Next we'll create the order assigning
         * the order type we have just created
         */
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
                        'unit_price'            =>  12,
                        'unit_quantity_id'      =>  $unit->id,
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
        
        $response->assertJsonPath( 'data.order.payment_status', Order::PAYMENT_PAID );
        $response   =   json_decode( $response->getContent(), true );
        $this->assertTrue( $response[ 'data' ][ 'order' ][ 'payments' ][0][ 'identifier' ] === 'paypal-payment', 'Invalid payment identifier detected.' );
    }
}
