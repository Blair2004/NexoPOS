<?php

namespace Tests\Feature;

use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerCoupon;
use App\Models\OrderPayment;
use App\Models\OrderProductRefund;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrencyService;
use Exception;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Faker\Factory;

class CreateOrderTest extends TestCase
{
    protected $customProductParams  =   [];
    protected $customOrderParams    =   [];
    protected $shouldRefund         =   false;
    protected $customDate           =   true;
    protected $shouldMakePayment    =   true;
    protected $count                =   5;
    protected $totalDaysInterval    =   30;
    protected $users                =   [];

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder( $callback = null )
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $faker          =   Factory::create();
        $startOfWeek    =   ns()->date->clone()->startOfWeek()->subDay();

        for( $i = 0; $i < $this->totalDaysInterval; $i++ ) {
            $date           =   $startOfWeek->addDay()->clone();
            $this->count    =   $this->count === false ? $faker->numberBetween(5,10) : $this->count;
            $this->output( sprintf( "\e[32mWill generate for the day \"%s\", %s order(s)", $date->toFormattedDateString(), $this->count ) );
            $this->processOrders( $date, $callback );
        }        
    }

    private function output( $message )
    {
        $fp = fopen('php://output', 'w');
        fwrite($fp, $message );
        fwrite($fp, "\n" );
        fclose($fp);
    }

    public function processOrders( $currentDate, $callback )
    {
        for( $i = 0; $i < $this->count; $i++ ) {

            /**
             * @var CurrencyService
             */
            $currency       =   app()->make( CurrencyService::class );
            $faker          =   Factory::create();
            $products       =   Product::with( 'unit_quantities' )->get()->shuffle()->take(3);
            $shippingFees   =   $faker->randomElement([10,15,20,25,30,35,40]);
            $discountRate   =   $faker->numberBetween(0,5);

            $products           =   $products->map( function( $product ) use ( $faker ) {
                $unitElement    =   $faker->randomElement( $product->unit_quantities );
                return array_merge([
                    'product_id'            =>  $product->id,
                    'quantity'              =>  $faker->numberBetween(1,10),
                    'unit_price'            =>  $unitElement->sale_price,
                    'unit_quantity_id'      =>  $unitElement->id,
                ], $this->customProductParams );
            })->filter( function( $product ) {
                return $product[ 'quantity' ] > 0;
            });

            /**
             * testing customer balance
             */
            $customer                   =   Customer::get()->random();
            $customerFirstPurchases     =   $customer->purchases_amount;
            $customerFirstOwed          =   $customer->owed_amount;

            $subtotal   =   ns()->currency->getRaw( $products->map( function( $product ) use ($currency) {
                return $currency
                    ->define( $product[ 'unit_price' ] )
                    ->multiplyBy( $product[ 'quantity' ] )
                    ->getRaw();
            })->sum() );

            $customerCoupon     =   CustomerCoupon::get()->last();

            if ( $customerCoupon instanceof CustomerCoupon ) {
                $allCoupons         =   [
                    [
                        'customer_coupon_id'    =>  $customerCoupon->id,
                        'coupon_id'             =>  $customerCoupon->coupon_id,
                        'name'                  =>  $customerCoupon->name,
                        'type'                  =>  'percentage_discount',
                        'code'                  =>  $customerCoupon->code,
                        'limit_usage'           =>  $customerCoupon->coupon->limit_usage,
                        'value'                 =>  $currency->define( $customerCoupon->coupon->discount_value )
                            ->multiplyBy( $subtotal )
                            ->divideBy( 100 )
                            ->getRaw(),
                        'discount_value'        =>  $customerCoupon->coupon->discount_value,
                        'minimum_cart_value'    =>  $customerCoupon->coupon->minimum_cart_value,
                        'maximum_cart_value'    =>  $customerCoupon->coupon->maximum_cart_value,
                    ]
                ];
    
                $totalCoupons   =   collect( $allCoupons )->map( fn( $coupon ) => $coupon[ 'value' ] )->sum();
            } else {
                $allCoupons             =   [];
                $totalCoupons           =   0;
            }

            $discount           =   [
                'type'      =>      $faker->randomElement([ 'percentage', 'flat' ]),
            ];

            /**
             * If the discount is percentage or flat.
             */
            if ( $discount[ 'type' ] === 'percentage' ) {
                $discount[ 'rate' ]     =   $discountRate;
                $discount[ 'value' ]    =   $currency->define( $discount[ 'rate' ] )
                    ->multiplyBy( $subtotal )
                    ->divideBy( 100 )
                    ->getRaw();
            } else {
                $discount[ 'value' ]    =   10;
                $discount[ 'rate' ]     =   0;
            }
            
            $discountCoupons    =   $currency->define( $discount[ 'value' ] )
                ->additionateBy( $allCoupons[0][ 'value' ] ?? 0 )
                ->getRaw();

            $dateString         =   $currentDate->startOfDay()->addHours( 
                $faker->numberBetween( 0,23 ) 
            )->format( 'Y-m-d H:m:s' );

            $orderData  =   array_merge([
                'customer_id'           =>  $customer->id,
                'type'                  =>  [ 'identifier' => 'takeaway' ],
                'discount_type'         =>  $discount[ 'type' ],
                'created_at'            =>  $this->customDate ? $dateString : null,
                'discount_percentage'   =>  $discount[ 'rate' ] ?? 0,
                'discount'              =>  $discount[ 'value' ] ?? 0,
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
                'author'                =>  ! empty( $this->users ) // we want to randomise the users
                    ? collect( $this->users )->suffle()->first()
                    : User::get( 'id' )->pluck( 'id' )->shuffle()->first(),
                'coupons'               =>  $allCoupons,
                'subtotal'              =>  $subtotal,
                'shipping'              =>  $shippingFees,
                'products'              =>  $products->toArray(),
                'payments'              =>  $this->shouldMakePayment ? [
                    [
                        'identifier'    =>  'cash-payment',
                        'value'         =>  $currency->define( $subtotal )
                            ->additionateBy( $shippingFees )
                            ->subtractBy( 
                                $discountCoupons
                            ) 
                            ->getRaw()
                    ]
                ] : []
            ], $this->customOrderParams );

            $response   =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/orders', $orderData );
            
            
            $response->assertJson([
                'status'    =>  'success'
            ]);

            if ( $this->shouldMakePayment ) {    
                $netsubtotal    =   $currency
                    ->define( $subtotal )
                    ->subtractBy( $totalCoupons )
                    ->subtractBy( $discount[ 'value' ] )
                    ->getRaw();
    
                $total          =   $currency->define( $netsubtotal )
                    ->additionateBy( $shippingFees )
                    ->getRaw() ;
    
                $response->assertJsonPath( 'data.order.subtotal',   $currency->getRaw( $subtotal ) );
                
                $response->assertJsonPath( 'data.order.total',      $currency->define( $netsubtotal )
                    ->additionateBy( $shippingFees )
                    ->getRaw() 
                );
    
                $response->assertJsonPath( 'data.order.change',     $currency->define( $subtotal + $shippingFees - ( $discount[ 'rate' ] + $totalCoupons ) )
                    ->subtractBy( $subtotal + $shippingFees - ( $discount[ 'rate' ] + ( $allCoupons[0][ 'value' ] ?? 0 ) ) )
                    ->getRaw() 
                );

                /**
                 * test if the order has updated
                 * correctly the customer account
                 */
                $customer->refresh();
                $customerSecondPurchases    =   $customer->purchases_amount;
                $customerSecondOwed         =   $customer->owed_amount;

                if ( ( float ) trim( $customerFirstPurchases + $total ) != ( float ) trim( $customerSecondPurchases ) ) {
                    throw new Exception( 
                        sprintf(
                            __( 'The customer purchase hasn\'t been updated. Expected %s Current Value %s. Sub total : %s' ),
                            $customerFirstPurchases + $total,
                            $customerSecondPurchases,
                            $total
                        )
                    );
                }
            }

            $responseData   =   json_decode( $response->getContent(), true );

            /**
             * Let's test wether the cash
             * flow has been created for this sale
             */
            $this->assertTrue( 
                CashFlow::where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )->first()
                instanceof CashFlow,
                __( 'No cash flow were created for this order.' )
            );

            /**
             * if a custom callback is provided
             * we'll call that callback as well
             */
            if ( is_callable( $callback ) ) {
                $callback( $response,  $responseData );
            }

            if ( $faker->randomElement([ true, false, false ]) === true && $this->shouldRefund ) {
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
