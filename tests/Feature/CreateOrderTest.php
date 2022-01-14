<?php

namespace Tests\Feature;

use App\Classes\Currency;
use App\Models\AccountType;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerCoupon;
use App\Models\OrderPayment;
use App\Models\OrderProductRefund;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\TaxService;
use Exception;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Faker\Factory;
use Illuminate\Support\Facades\Event;

class CreateOrderTest extends TestCase
{
    protected $customProductParams  =   [];
    protected $customOrderParams    =   [];
    protected $processCoupon        =   true;
    protected $useDiscount          =   true;
    protected $shouldRefund         =   true;
    protected $customDate           =   true;
    protected $shouldMakePayment    =   true;
    protected $count                =   1;
    protected $totalDaysInterval    =   1;
    protected $users                =   [];
    protected $defaultProcessing    =   true;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder( $callback = null )
    {
        if ( $this->defaultProcessing ) {
            Sanctum::actingAs(
                Role::namespace( 'admin' )->users->first(),
                ['*']
            );
    
            $faker          =   Factory::create();
            $responses      =   [];
            $startOfWeek    =   ns()->date->clone()->startOfWeek()->subDays($this->totalDaysInterval);
    
            for( $i = 0; $i < $this->totalDaysInterval; $i++ ) {
                $date           =   $startOfWeek->addDay()->clone();
                $this->count    =   $this->count === false ? $faker->numberBetween(5,10) : $this->count;
                $this->output( sprintf( "\e[32mWill generate for the day \"%s\", %s order(s)", $date->toFormattedDateString(), $this->count ) );
                $responses[]    =   $this->processOrders( $date, $callback );
            }        
    
            return $responses;
        } else {
            $this->assertTrue( true ); // because we haven't performed any test.
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
        $responses      =   [];
        /**
         * @var CurrencyService
         */
        $currency       =   app()->make( CurrencyService::class );
        $faker          =   Factory::create();

        /**
         * @var TaxService
         */
        $taxService     =   app()->make( TaxService::class );

        for( $i = 0; $i < $this->count; $i++ ) {

            $singleResponse     =   [];
            
            $products       =   Product::with( 'unit_quantities' )->get()->shuffle()->take(3);
            $shippingFees   =   $faker->randomElement([10,15,20,25,30,35,40]);
            $discountRate   =   $faker->numberBetween(0,5);

            $products           =   $products->map( function( $product ) use ( $faker, $taxService ) {
                $unitElement    =   $faker->randomElement( $product->unit_quantities );
                $discountRate   =   10;
                $data           =   array_merge([
                    'name'                  =>  $product->name,
                    'discount'              =>  $taxService->getPercentageOf( $unitElement->sale_price, $discountRate ),
                    'discount_percentage'   =>  $discountRate,
                    'quantity'              =>  $faker->numberBetween(1,10),
                    'unit_price'            =>  $unitElement->sale_price,
                    'tax_type'              =>  'inclusive',
                    'tax_group_id'          =>  1,
                    'unit_id'               =>  $unitElement->unit_id,
                ], $this->customProductParams );

                if ( $faker->randomElement([ false, true ]) ) {
                    $data[ 'product_id' ]       =   $product->id;
                    $data[ 'unit_quantity_id' ] =   $unitElement->id;
                }

                return $data;
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

            if ( $customerCoupon instanceof CustomerCoupon && $this->processCoupon ) {
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
                'type'      =>  $faker->randomElement([ 'percentage' ]),
                'rate'      =>  5,
            ];

            if ( $this->useDiscount ) {
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
                    $discount[ 'value' ]    =   Currency::fresh( $subtotal )
                        ->divideBy( 2 )
                        ->getRaw();

                    $discount[ 'rate' ]     =   0;
                }
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

            $singleResponse[ 'order-creation' ]   =   json_decode( $response->getContent(), true );

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

                $singleResponse[ 'order-payment' ]   =   json_decode( $response->getContent() );

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
            if ( $responseData[ 'data' ][ 'order' ][ 'payment_status' ] !== 'unpaid' ) {
                $this->assertTrue( 
                    CashFlow::where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )->first()
                    instanceof CashFlow,
                    __( 'No cash flow were created for this order.' )
                );
            }

            /**
             * if a custom callback is provided
             * we'll call that callback as well
             */
            if ( is_callable( $callback ) ) {
                $callback( $response,  $responseData );
            }

            if ( $faker->randomElement([ true ]) === true && $this->shouldRefund ) {
                /**
                 * We'll keep original products amounts and quantity
                 * this means we're doing a full refund of price and quantities
                 */
                $productCondition   =   $faker->randomElement([
                    OrderProductRefund::CONDITION_DAMAGED,
                    OrderProductRefund::CONDITION_UNSPOILED,
                ]);

                $products   =   collect( $responseData[ 'data' ][ 'order' ][ 'products' ] )->map( function( $product ) use ( $faker, $productCondition ) {
                    return array_merge( $product, [
                        'condition'     =>  $productCondition,
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

                /**
                 * A single cash flow should be
                 * created for that order for the sale account
                 */
                $totalCashFlow  =   CashFlow::where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
                    ->where( 'operation', CashFlow::OPERATION_CREDIT )
                    ->where( 'expense_category_id', ns()->option->get( 'ns_sales_cashflow_account' ) )
                    ->count();

                $this->assertTrue( $totalCashFlow === 1, 'More than 1 cash flow was created for the sale account.' );

                /**
                 * all refund transaction give a stock flow record.
                 * We need to check if it has been created.
                 */
                $totalRefundedCashFlow   =   CashFlow::where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
                    ->where( 'operation', CashFlow::OPERATION_DEBIT )
                    ->where( 'expense_category_id', ns()->option->get( 'ns_sales_refunds_account' ) )
                    ->count();

                $this->assertTrue( $totalRefundedCashFlow === $products->count(), 'Not enough cash flow entry were created for the refunded product' );

                /**
                 * in case the order is refunded with 
                 * some defective products, we need to check if
                 * the waste expense has been created.
                 */
                if ( $productCondition === OrderProductRefund::CONDITION_DAMAGED ) {
                    $totalSpoiledCashFlow   =   CashFlow::where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
                        ->where( 'operation', CashFlow::OPERATION_DEBIT )
                        ->where( 'expense_category_id', ns()->option->get( 'ns_stock_return_spoiled_account' ) )
                        ->count();

                    $this->assertTrue( $totalSpoiledCashFlow === $products->count(), 'Not enough cash flow entry were created for the refunded product' );
                }

                $singleResponse[ 'order-refund' ]   =   json_decode( $response->getContent() );
            }

            $responses[]    =   $singleResponse;
        }

        return $responses;
    }
}
