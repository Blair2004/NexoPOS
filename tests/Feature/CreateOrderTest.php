<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OrderPayment;
use App\Models\OrderProductRefund;
use App\Models\Product;
use App\Models\Role;
use App\Services\CurrencyService;
use Exception;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Faker\Factory;

class CreateOrderTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        for( $i = 0; $i < 1; $i++ ) {
            /**
             * @var CurrencyService
             */
            $currency       =   app()->make( CurrencyService::class );
            $faker          =   Factory::create();
            $products       =   Product::with( 'unit_quantities' )->get()->shuffle()->take(3);
            $shippingFees   =   $faker->randomElement([100,150,200,250,300,350,400]);
            $discountRate   =   $faker->numberBetween(0,5);

            $products       =   $products->map( function( $product ) use ( $faker ) {
                $unitElement    =   $faker->randomElement( $product->unit_quantities );
                return [
                    'product_id'            =>  $product->id,
                    'quantity'              =>  $faker->numberBetween(1,10),
                    'unit_price'            =>  $unitElement->sale_price,
                    'unit_quantity_id'      =>  $unitElement->id,
                ];
            });

            /**
             * testing customer balance
             */
            $customer                   =   Customer::first();
            $customerFirstPurchases     =   $customer->purchases_amount;
            $customerFirstOwed          =   $customer->owed_amount;

            $subtotal   =   ns()->currency->getRaw( $products->map( function( $product ) use ($currency) {
                return $currency
                    ->define( $product[ 'unit_price' ] )
                    ->multiplyBy( $product[ 'quantity' ] )
                    ->getRaw();
            })->sum() );

            $response   =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/orders', [
                    'customer_id'           =>  $customer->id,
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
                    'products'              =>  $products->toArray(),
                    'payments'              =>  [
                        [
                            'identifier'    =>  'cash-payment',
                            'value'         =>  $subtotal + $shippingFees
                        ]
                    ]
                ]);
            
            
                $response->assertJson([
                'status'    =>  'success'
            ]);

            $discount       =   $currency->define( $discountRate )
                ->multipliedBy( $subtotal )
                ->divideBy( 100 )
                ->getRaw();

            $netsubtotal    =   $currency
                ->define( $subtotal )
                ->subtractBy( $discount )
                ->getRaw();

            $total          =   $currency->define( $netsubtotal )
                ->additionateBy( $shippingFees )
                ->getRaw() ;

            $response->assertJsonPath( 'data.order.subtotal',   $currency->getRaw( $subtotal ) );
            
            $response->assertJsonPath( 'data.order.total',      $currency->define( $netsubtotal )
                ->additionateBy( $shippingFees )
                ->getRaw() 
            );

            $response->assertJsonPath( 'data.order.change',     $currency->define( $netsubtotal )
                ->additionateBy( $shippingFees )
                ->subtractBy( $currency->define( $netsubtotal )->additionateBy( $shippingFees )->getRaw() )
                ->additionateBy( $discount )
                ->getRaw() 
            );

            $responseData   =   json_decode( $response->getContent(), true );

            /**
             * test if the order has updated
             * correctly the customer account
             */
            $customer->refresh();
            $customerSecondPurchases    =   $customer->purchases_amount;
            $customerSecondOwed         =   $customer->owed_amount;

            if ( $customerFirstPurchases + $responseData[ 'data' ][ 'order' ][ 'tendered' ] != $customerSecondPurchases ) {
                throw new Exception( 
                    sprintf(
                        __( 'The customer purchase hasn\'t been updated. Expected %s Current Value %s. Sub total : %s' ),
                        $customerFirstPurchases + $total,
                        $customerSecondPurchases,
                        $total
                    )
                );
            }

            if ( $faker->randomElement([ true, false, false ]) === true ) {
                /**
                 * We'll keep original products amounts and quantity
                 * this means we're doing a full refund of price and quantities
                 */
                $products   =   collect( $responseData[ 'data' ][ 'order' ][ 'products' ] )->map( function( $product ) use ( $faker ) {
                    return array_merge( $product, [
                        'condition'     =>  $faker->randomElement([
                            OrderProductRefund::CONDITION_DAMAGED,
                            OrderProductRefund::CONDITION_UNSPOILED,
                        ]),
                        'description'   =>  __( 'A random description from the refund test' ),
                        'quantity'      =>  $faker->randomElement([
                            $product[ 'quantity' ],
                            // floor( $product[ 'quantity' ] / 2 )
                        ])
                    ]);
                });

                $response   =   $this->withSession( $this->app[ 'session' ]->all() )
                    ->json( 'POST', 'api/nexopos/v4/orders/' . $responseData[ 'data' ][ 'order' ][ 'id' ] . '/refund', [
                        'payment'   =>  [
                            'identifier'    =>  $faker->randomElement([
                                OrderPayment::PAYMENT_ACCOUNT,
                                OrderPayment::PAYMENT_CASH
                            ]),
                        ],
                        'refund_shipping'   =>  $faker->randomElement([ true, false ]),
                        'total'             =>  collect( $products )
                            ->map( fn( $product ) => 
                                $currency
                                    ->define( $product[ 'quantity' ] )
                                    ->multiplyBy( $product[ 'unit_price' ] )
                                    ->getRaw() 
                            )->sum(),
                        'products'  =>  $products,
                    ]);
                
                $response->assertJson([
                    'status'    =>  'success'
                ]);
            }
        }
    }
}
