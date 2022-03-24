<?php
namespace Tests\Traits;

use App\Classes\Currency;
use App\Exceptions\NotAllowedException;
use App\Models\RewardSystem;
use App\Models\AccountType;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerCoupon;
use App\Models\Order;
use App\Models\OrderInstalment;
use App\Models\OrderPayment;
use App\Models\OrderProduct;
use App\Models\OrderProductRefund;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\ProductUnitQuantity;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Models\TaxGroup;
use App\Models\User;
use App\Services\CashRegistersService;
use App\Services\CurrencyService;
use App\Services\OrdersService;
use App\Services\ProductService;
use App\Services\TaxService;
use App\Services\TestService;
use Exception;
use Faker\Factory;
use Illuminate\Foundation\Testing\WithFaker;

trait WithOrderTest
{
    use WithFaker;

    protected $customProductParams  =   [];
    protected $customOrderParams    =   [];
    protected $processCoupon        =   true;
    protected $useDiscount          =   true;
    protected $shouldRefund         =   false;
    protected $customDate           =   true;
    protected $shouldMakePayment    =   true;
    protected $count                =   1;
    protected $totalDaysInterval    =   1;
    protected $users                =   [];
    protected $defaultProcessing    =   true;
    protected $allowQuickProducts   =   true;

    protected function attemptPostOrder( $callback )
    {
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
    }
    
    protected function attemptCreateOrderOnRegister( $data = [] )
    {
        RegisterHistory::truncate();

        Register::where( 'id', '>', 0 )->update([
            'balance'   =>  0
        ]);

        $cashRegister   =   Register::first();
        $previousValue  =   $cashRegister->balance;

        /**
         * @var CashRegistersService
         */
        $cashRegisterService    =   app()->make( CashRegistersService::class );

        /**
         * Just in case it's opened
         */
        try {
            $cashRegisterService->closeRegister( $cashRegister, 0, __( 'Attempt closing' ) );
            $cashRegister->refresh();
        } catch( NotAllowedException $exception ) {
            // it's probably not opened, let's proceed...
        }

        $result     =   $cashRegisterService->openRegister( $cashRegister, 100, __( 'Opening the cash register' ) );
        $previousValue  =   $result[ 'data' ][ 'history' ]->value;
        
        /**
         * Step 1 : let's prepare the order
         * before submitting that.
         */
        $response   =   $this->registerOrderForCashRegister( $cashRegister, $data[ 'orderData' ] ?? [] ); 

        /**
         * between each operation
         * we need to refresh the cash register
         */
        $cashRegister->refresh();

        /**
         * only if the order total is greater than 0
         */
        if( (float) $response[ 'data' ][ 'order' ][ 'tendered' ] > 0 ) {
            $this->assertNotEquals( $cashRegister->balance, $previousValue, __( 'There hasn\'t been any change during the transaction on the cash register balance.' ) );
            $this->assertEquals( ( float ) $cashRegister->balance, ( float ) ( $previousValue + $response[ 'data' ][ 'order' ][ 'total' ] ), __( 'The cash register balance hasn\'t been updated correctly.' ) );
        }
        
        /**
         * Step 2 : disburse (cash-out) some cash
         * from the provided register
         */
        $this->disburseCashFromRegister( $cashRegister, $cashRegisterService );
        
        /**
         * between each operation
         * we need to refresh the cash register
         */
        $cashRegister->refresh();

        $this->assertNotEquals( $cashRegister->balance, $previousValue, __( 'There hasn\'t been any change during the transaction on the cash register balance.' ) );

        /**
         * Step 3 : cash in some cash
         */
        $this->cashInOnRegister( $cashRegister, $cashRegisterService );

        /**
         * We neet to refresh the register
         * to make sure it has the updated values.
         */
        $cashRegister->refresh();

        $this->assertNotEquals( $cashRegister->balance, $previousValue, __( 'There hasn\'t been any change during the transaction on the cash register balance.' ) );

        /**
         * Let's initialize the total transactions 
         */
        $totalTransactions      =   0;

        /**
         * last time the cash register has opened
         */
        $opening    =   RegisterHistory::action( RegisterHistory::ACTION_OPENING )->orderBy( 'id', 'desc' )->first();

        /**
         * We'll start by computing orders
         */
        $openingBalance         =  ( float ) $opening->value;

        $totalCashing           =  RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_CASHING )->sum( 'value' );

        $totalSales             =  RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_SALE )->sum( 'value' );

        $totalClosing           =  RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_CLOSING )->sum( 'value' );

        $totalCashOut           =  RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_CASHOUT )->sum( 'value' );

        $totalRefunds           =  RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_REFUND )->sum( 'value' );

        $totalTransactions      =   ( $openingBalance + $totalCashing + $totalSales ) - ( $totalClosing + $totalRefunds + $totalCashOut );

        $this->assertEquals( 
            ns()->currency->getRaw( $cashRegister->balance ), 
            ns()->currency->getRaw( $totalTransactions ), 
            __( 'The transaction aren\'t reflected on the register balance' ) 
        );

        return compact( 'response', 'cashRegister' );
    }

    public function attemptUpdateOrderOnRegister()
    {
        /**
         * @var OrdersService $orderService
         */
        $orderService   =   app()->make( OrdersService::class );

        $result         =   $this->attemptCreateOrderOnRegister([
            'orderData' =>  [
                'payments'  =>  [], // we'll disable payments.
            ]
        ]);

        extract( $result );
        /**
         * @var array $response
         * @var Register $cashRegister
         */
        $order              =   Order::find( $response[ 'data' ][ 'order' ][ 'id' ] );
        $orderService->makeOrderSinglePayment([
            'identifier'    =>  OrderPayment::PAYMENT_CASH,
            'value'         =>  $response[ 'data' ][ 'order' ][ 'total' ],
        ], $order );

        /**
         * Making assertions
         */
        $cashRegisterHistory    =   RegisterHistory::where( 'register_id', $cashRegister->id )->orderBy( 'id', 'desc' )->first();
        
        $this->assertTrue( 
            ns()->currency->getRaw( $cashRegisterHistory->value ) === $order->total,
            __( 'The payment wasn\'t added to the cash register history' )
        );
    }

    private function registerOrderForCashRegister( Register $cashRegister, $data )
    {
        /**
         * @var TestService
         */
        $testService    =   app()->make( TestService::class );

        $orderDetails   =   $testService->prepareOrder( ns()->date->now(), array_merge([
            'register_id'   =>  $cashRegister->id
        ], $data ) );

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/orders', $orderDetails );
        
        $response->assertStatus( 200 );

        return $response   =   json_decode( $response->getContent(), true );
    }

    /**
     * Will disburse the cash register
     * @param Register $cashRegister
     * @param CashRegistersService $cashRegisterService
     * @return void
     */
    private function disburseCashFromRegister( Register $cashRegister, CashRegistersService $cashRegistersService )
    {
        $cashRegistersService->cashOut( $cashRegister, $cashRegister->balance / 1.5, __( 'Test disbursing the cash register' ) );
    }

    /**
     * Will disburse the cash register
     * @param Register $cashRegister
     * @param CashRegistersService $cashRegisterService
     * @return void
     */
    private function cashInOnRegister( Register $cashRegister, CashRegistersService $cashRegistersService )
    {
        $cashRegistersService->cashIn( $cashRegister, ( $cashRegister->balance / 2 ), __( 'Test disbursing the cash register' ) );
    }
    
    protected function attemptCreateCustomerOrder()
    {
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
    
    protected function attemptCreateCustomPaymentType()
    {
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
                    'active'        =>  true,
                    'priority'      =>  0,
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);
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
                $quantity       =   $faker->numberBetween(1,10);
                $data           =   array_merge([
                    'name'                  =>  $product->name,
                    'discount'              =>  $taxService->getPercentageOf( $unitElement->sale_price, $discountRate ) * $quantity,
                    'discount_percentage'   =>  $discountRate,
                    'discount_type'         =>  $faker->randomElement([ 'flat', 'percentage' ]),
                    'quantity'              =>  $quantity,
                    'unit_price'            =>  $unitElement->sale_price,
                    'tax_type'              =>  'inclusive',
                    'tax_group_id'          =>  1,
                    'unit_id'               =>  $unitElement->unit_id,
                ], $this->customProductParams );

                if ( ! $this->allowQuickProducts ) {
                    $data[ 'product_id' ]       =   $product->id;
                    $data[ 'unit_quantity_id' ] =   $unitElement->id;
                } else if ( $faker->randomElement([ true, false ]) ) {
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

            $subtotal   =   ns()->currency->getRaw( $products->map( function( $product ) use ($currency) {
                $productSubTotal    =   $currency
                    ->fresh( $product[ 'unit_price' ] )
                    ->multiplyBy( $product[ 'quantity' ] )
                    ->subtractBy( $product[ 'discount' ] )
                    ->getRaw();
                
                return $productSubTotal;
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

            $discountCoupons    =   0;
            
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

                $discountCoupons    =   $currency->define( $discount[ 'value' ] )
                    ->additionateBy( $allCoupons[0][ 'value' ] ?? 0 )
                    ->getRaw();
            }
            

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

            $customer                   =   Customer::find( $orderData[ 'customer_id' ] );
            $customerFirstPurchases     =   $customer->purchases_amount;
            $customerFirstOwed          =   $customer->owed_amount;

            $response   =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/orders', $orderData );
                            
            $response->assertJson([
                'status'    =>  'success'
            ]);

            $singleResponse[ 'order-creation' ]   =   json_decode( $response->getContent(), true );

            if ( $this->shouldMakePayment ) {    
                $netsubtotal    =   $currency
                    ->define( $orderData[ 'subtotal' ] )
                    ->subtractBy( $totalCoupons )
                    ->subtractBy( $orderData[ 'discount' ] )
                    ->getRaw();
    
                $total          =   $currency->define( $netsubtotal )
                    ->additionateBy( $orderData[ 'shipping' ] )
                    ->getRaw() ;

                $response->assertJsonPath( 'data.order.subtotal', $currency->getRaw( $orderData[ 'subtotal' ] ) );

                $response->assertJsonPath( 'data.order.total', $currency->define( $netsubtotal )
                    ->additionateBy( $orderData[ 'shipping' ] )
                    ->getRaw() 
                );
    
                $couponValue    =   ( ! empty( $orderData[ 'coupons' ] ) ? ( float ) $orderData[ 'coupons' ][0][ 'value' ] : 0 );
                $totalPayments  =   collect( $orderData[ 'payments' ] )->map( fn( $payment ) => ( float ) $payment[ 'value' ] )->sum() ?: 0;
                $change         =   $totalPayments - (  ( float ) $orderData[ 'subtotal' ] + ( float ) $orderData[ 'shipping' ] - ( float ) $orderData[ 'discount' ] - $couponValue );
                $change         =   Currency::raw( $change );

                $response->assertJsonPath( 'data.order.change', $change );

                $singleResponse[ 'order-payment' ]   =   json_decode( $response->getContent() );

                /**
                 * test if the order has updated
                 * correctly the customer account
                 */
                $customer->refresh();
                $customerSecondPurchases    =   $customer->purchases_amount;
                $customerSecondOwed         =   $customer->owed_amount;

                if ( ( float ) trim( $customerFirstPurchases + ( $orderData[ 'payments' ][0][ 'value' ] ?? 0 ) ) != ( float ) trim( $customerSecondPurchases ) ) {
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

    protected function attemptOrderWithProductPriceMode()
    {
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

    protected function attemptCreateHoldOrder()
    {
        $unitQuantity   =   ProductUnitQuantity::where( 'quantity', '>', 0 )->get()->random();

        if ( ! $unitQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( 'No valid unit is provided.' );
        }

        $subtotal   =   $unitQuantity->sale_price * 5;

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
                'payment_status'        =>  'hold',
                'subtotal'              =>  $subtotal,
                'shipping'              =>  150,
                'products'              =>  [
                    [
                        'product_id'            =>  $unitQuantity->product->id,
                        'quantity'              =>  5,
                        'unit_price'            =>  12,
                        'unit_quantity_id'      =>  $unitQuantity->id,
                    ]
                ],
            ]);
        
        $response->assertJsonPath( 'data.order.payment_status', 'hold' );

        return json_decode( $response->getContent(), true );
    }

    protected function attemptDeleteOrder()
    {
        /**
         * @var ProductService
         */
        $productService     =   app()->make( ProductService::class );

        $order      =   Order::paid()->first();
        $products   =  $order->products
            ->filter( fn( $product ) => $product->product_id > 0 )
            ->map( function( $product ) use ( $productService ) {
            $product->previous_quantity   =   $productService->getQuantity( $product->product_id, $product->unit_id );
            return $product;
        });

        /**
         * let's check if the order has a cash flow entry
         */
        $this->assertTrue( CashFlow::where( 'order_id', $order->id )->first() instanceof CashFlow, 'No cash flow created for the order.' );

        if ( $order instanceof Order ) {

            $order_id   =   $order->id;

            /**
             * @var OrdersService
             */
            $orderService   =   app()->make( OrdersService::class );
            $orderService->deleteOrder( $order );

            $totalPayments    =   OrderPayment::where( 'order_id', $order_id )->count();

            $this->assertTrue( $totalPayments === 0, 
                sprintf(
                    __( 'An order payment hasn\'t been deleted along with the order (%s).' ),
                    $order->id
                )
            );

            /**
             * let's check if flow entry has been removed
             */
            $this->assertTrue( ! CashFlow::where( 'order_id', $order->id )->first() instanceof CashFlow, 'The cash flow hasn\'t been deleted.' );

            $products->each( function( OrderProduct $orderProduct ) use ( $productService ){
                $originalProduct                =   $orderProduct->product;

                if ( $originalProduct->stock_management === Product::STOCK_MANAGEMENT_ENABLED ) {
                    $orderProduct->actual_quantity   =   $productService->getQuantity( $orderProduct->product_id, $orderProduct->unit_id );
    
                    /**
                     * Let's check if the quantity has been restored 
                     * to the default value.
                     */
                    $this->assertTrue( 
                        ( float ) $orderProduct->actual_quantity == ( float ) $orderProduct->previous_quantity + ( float ) $orderProduct->quantity,
                        __( 'The new quantity was not restored to what it was before the deletion.')
                    );
                }
            });

        } else {
            throw new Exception( __( 'No order where found to perform the test.' ) );
        }
    }

    protected function attemptRefundOrder()
    {
        /**
         * @var CurrencyService
         */
        $currency       =   app()->make( CurrencyService::class );
        
        $firstFetchCustomer     =   Customer::first();        
        $firstFetchCustomer->save();

        $product        =   Product::withStockEnabled()->with( 'unit_quantities' )->first();
        $shippingFees   =   150;
        $discountRate   =   3.5;
        $products       =   [
            /**
             * this is a sample product/service
             */
            [
                'quantity'              =>  5,
                'unit_price'            =>  $product->unit_quantities[0]->sale_price,
                'unit_quantity_id'      =>  $product->unit_quantities[0]->id,
                'unit_id'               =>  $product->unit_quantities[0]->unit_id
            ], 
            
            /**
             * An existing product
             */
            [
                'product_id'            =>  $product->id,
                'quantity'              =>  5,
                'unit_price'            =>  $product->unit_quantities[0]->sale_price,
                'unit_quantity_id'      =>  $product->unit_quantities[0]->id,
                'unit_id'               =>  $product->unit_quantities[0]->unit_id
            ]
        ];

        $subtotal   =   collect( $products )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();
        $netTotal   =   $subtotal   +   $shippingFees;
        
        /**
         * We'll add taxes to the order in
         * case we have some tax group defined.
         */
        $taxes      =   [];
        $taxGroup   =   TaxGroup::first();
        if ( $taxGroup instanceof TaxGroup ) {
            $taxes  =   $taxGroup->taxes->map( function( $tax ) {
                return [
                    'tax_name'  =>  $tax->name,
                    'tax_id'    =>  $tax->id,
                    'rate'      =>  $tax->rate
                ];
            });
        }

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id'           =>  $firstFetchCustomer->id,
                'type'                  =>  [ 'identifier' => 'takeaway' ],
                'discount_type'         =>  'percentage',
                'discount_percentage'   =>  $discountRate,
                'taxes'                 =>  $taxes,
                'tax_group_id'          =>  $taxGroup->id,
                'tax_type'              =>  'inclusive',
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
                        'value'         =>  $netTotal
                    ]
                ]
            ]);        
        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $responseData           =   json_decode( $response->getContent(), true );
        
        $secondFetchCustomer    =   $firstFetchCustomer->fresh();
        
        if ( $currency->define( $secondFetchCustomer->purchases_amount )
            ->subtractBy( $responseData[ 'data' ][ 'order' ][ 'tendered' ] )
            ->getRaw() != $currency->getRaw( $firstFetchCustomer->purchases_amount ) ) {
            throw new Exception( 
                sprintf(
                    __( 'The purchase amount hasn\'t been updated correctly. Expected %s, got %s' ),
                    $secondFetchCustomer->purchases_amount - ( float ) $responseData[ 'data' ][ 'order' ][ 'tendered' ],
                    $firstFetchCustomer->purchases_amount
                )
            );
        }

        /**
         * We'll keep original products amounts and quantity
         * this means we're doing a full refund of price and quantities
         */
        $responseData[ 'data' ][ 'order' ][ 'products' ]    =   collect( $responseData[ 'data' ][ 'order' ][ 'products' ] )->map( function( $product ) {
            $product[ 'condition' ]     =   OrderProductRefund::CONDITION_DAMAGED;
            $product[ 'quantity' ]      =   1;
            $product[ 'description' ]   =   __( 'Test : The product wasn\'t properly manufactured, causing external damage to the device during the shipment.' );
            return $product;
        })->toArray();

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $responseData[ 'data' ][ 'order' ][ 'id' ] . '/refund', [
                'payment'   =>  [
                    'identifier'    =>  'account-payment',
                ],
                'total'     =>  $responseData[ 'data' ][ 'order' ][ 'total' ],
                'products'  =>  $responseData[ 'data' ][ 'order' ][ 'products' ],
            ]);

        $response->assertStatus(200);
        $responseData           =   json_decode( $response->getContent(), true );

        $thirdFetchCustomer      =   $secondFetchCustomer->fresh();

        if ( 
            $currency->define( $thirdFetchCustomer->purchases_amount )
                ->additionateBy( $responseData[ 'data' ][ 'orderRefund' ][ 'total' ] )
                ->getRaw() !== $currency->getRaw( $secondFetchCustomer->purchases_amount ) ) {

            throw new Exception( 
                sprintf(
                    __( 'The purchase amount hasn\'t been updated correctly. Expected %s, got %s' ),
                    $secondFetchCustomer->purchases_amount,
                    $thirdFetchCustomer->purchases_amount + ( float ) $responseData[ 'data' ][ 'orderRefund' ][ 'total' ]
                )
            );
        }

        /**
         * let's check if an expense has been created accordingly
         */
        // ns_sales_refunds_cashflow_account
        $expenseCategory    =   AccountType::find( ns()->option->get( 'ns_sales_refunds_account' ) );

        if ( ! $expenseCategory instanceof AccountType ) {
            throw new Exception( __( 'An expense hasn\'t been created after the refund.' ) );
        }

        $expenseValue    =   $expenseCategory->cashFlowHistories()
            ->where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
            ->sum( 'value' );

        if ( ( float ) $expenseValue != ( float ) $responseData[ 'data' ][ 'orderRefund' ][ 'total' ] ) {
            throw new Exception( __( 'The expense created after the refund doesn\'t match the order refund total.' ) );
        }  
        
        $response->assertJson([
            'status'    =>  'success'
        ]);
    }

    public function attemptCreateOrderWithInstalment()
    {
        /**
         * @var CurrencyService
         */
        $currency       =   app()->make( CurrencyService::class );

        /**
         * @var OrdersService
         */
        $orderService   =   app()->make( OrdersService::class );
        $faker          =   Factory::create();
        $products       =   Product::with( 'unit_quantities' )->get()->shuffle()->take(1);
        $shippingFees   =   $faker->randomElement([100,150,200,250,300,350,400]);
        $discountRate   =   $faker->numberBetween(1,5);

        $products       =   $products->map( function( $product ) use ( $faker ) {
            $unitElement    =   $faker->randomElement( $product->unit_quantities );
            return [
                'product_id'            =>  $product->id,
                'quantity'              =>  $faker->numberBetween(1,10), // 2,
                'unit_price'            =>  $unitElement->sale_price, // 110.8402,
                'unit_quantity_id'      =>  $unitElement->id,
            ];
        });

        /**
         * testing customer balance
         */
        $customer                   =   Customer::first();

        $subtotal   =   ns()->currency->getRaw( $products->map( function( $product ) use ($currency) {
            return Currency::raw( $product[ 'unit_price' ] ) * Currency::raw( $product[ 'quantity' ] );
        })->sum() );

        $initialTotalInstallment    =   2;
        $discountValue              =   $orderService->computeDiscountValues( $discountRate, $subtotal );
        $total                      =   ns()->currency->getRaw( ( $subtotal + $shippingFees ) - $discountValue );

        $paymentAmount              =   ns()->currency->getRaw( $total / 2 );

        $instalmentSlice            =   $total / 2;
        $instalmentPayment          =   ns()->currency->getRaw( $instalmentSlice );

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id'           =>  $customer->id,
                'type'                  =>  [ 'identifier' => 'takeaway' ],
                // 'discount_type'         =>  'percentage',
                'discount_percentage'   =>  $discountRate,
                'discount_type'         =>  'flat',
                'discount'              =>  $discountValue,
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
                'coupons'               =>  [],
                'subtotal'              =>  $subtotal,
                'shipping'              =>  $shippingFees,
                'total'                 =>  $total,
                'tendered'              =>  ns()->currency
                    ->getRaw( $total / 2 ),
                'total_instalments'     =>  $initialTotalInstallment,
                'instalments'           =>  [
                    [
                        'date'          =>  ns()->date->getNowFormatted(),
                        'amount'        =>  $instalmentPayment
                    ], [
                        'date'          =>  ns()->date->copy()->addDays(2)->toDateTimeString(),
                        'amount'        =>  $instalmentPayment
                    ]
                ],
                'products'              =>  $products->toArray(),
                'payments'              =>  [
                    [
                        'identifier'    =>  'cash-payment',
                        'value'         =>  $paymentAmount,
                    ]
                ]
            ]);
        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $responseData   =   json_decode( $response->getContent(), true );

        /**
         * Editing the instalment
         */
        $today              =   ns()->date->toDateTimeString();
        $order              =   $responseData[ 'data' ][ 'order' ];
        $instalment         =   OrderInstalment::where( 'order_id', $order[ 'id' ] )->where( 'paid', false )->get()->random();
        $instalmentAmount   =   ns()->currency->getRaw( $instalment->amount / 2 );
        $response           =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments/' . $instalment->id, [
                'instalment'    =>  [
                    'date'      =>  $today,
                    'amount'    =>  $instalmentAmount
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $instalment->refresh();

        if ( $instalment->date != $today ) {
            throw new Exception( __( 'The modification of the instalment has failed' ) );
        }

        /**
         * Add instalment
         */
        $order          =   Order::find( $order[ 'id' ] );
        $oldInstlaments =   $order->total_instalments;
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments', [
                'instalment'    =>  [
                    'date'      =>  $today,
                    'amount'    =>  $instalmentAmount
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $order->refresh();

        if ( $initialTotalInstallment === $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been registered.' ) );
        }

        if ( $oldInstlaments >= $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been registered.' ) );
        }

        $responseData   =   json_decode( $response->getContent(), true );

        /**
         * Delete Instalment
         */
        $order          =   Order::find( $order[ 'id' ] );
        $oldInstlaments =   $order->total_instalments;
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments/' . $responseData[ 'data' ][ 'instalment' ][ 'id' ] );

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $order->refresh();

        if ( $oldInstlaments === $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been deleted.' ) );
        }

        /**
         * restore deleted instalment
         */
        $order          =   Order::find( $order[ 'id' ] );
        $oldInstlaments =   $order->total_instalments;
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments', [
                'instalment'    =>  [
                    'date'      =>  $today,
                    'amount'    =>  $instalmentAmount
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $order->refresh();

        if ( $oldInstlaments >= $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been registered.' ) );
        }

        $responseData   =   json_decode( $response->getContent(), true );

        /**
         * paying instalment
         */
        
        OrderInstalment::where( 'order_id', $order->id )
            ->where( 'paid', false )
            ->get()
            ->each( function( $instalment ) use ( $order ) {
                $response       =   $this->withSession( $this->app[ 'session' ]->all() )
                    ->json( 'POST', 'api/nexopos/v4/orders/' . $order->id . '/instalments/' . $instalment->id . '/pay', [
                        'payment_type'  =>  OrderPayment::PAYMENT_CASH
                    ]);

                $response->assertJson([
                    'status'    =>  'success'
                ]);

                $instalment->refresh();

                $this->assertTrue( $instalment->paid, __( 'The instalment hasn\'t been paid.' ) );
        });
    }

    protected function attemptCreatePartiallyPaidOrderWithAdjustment()
    {
        $currency       =   app()->make( CurrencyService::class );
        
        $customer       =   Customer::first();
        $customer->credit_limit_amount   =   0;
        $customer->save();

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
                'products'              =>  $products,
                'payments'              =>  [],
                'payment_status'        =>  Order::PAYMENT_UNPAID,
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);

        $responseData   =   json_decode( $response->getContent(), true );

        /**
         * performing the adjustment by increasing the quantity 
         * that is added to the order.
         */
        $product    =   Product::with( 'unit_quantities' )->find(1);

        $responseData[ 'data' ][ 'order' ][ 'products' ][0][ 'quantity' ]++;

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
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $responseData[ 'data' ][ 'order' ][ 'id' ], [
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
                'products'              =>  $responseData[ 'data' ][ 'order' ][ 'products' ],
                'payments'              =>  []
            ]);
        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        $response->assertStatus(200);
    }

    protected function attemptTestRewardSystem()
    {
        $reward             =   RewardSystem::with( 'rules' )->first();
        $rules              =   $reward->rules->sortBy( 'reward' )->reverse();
        $timesForOrders     =   ( $reward->target / $rules->first()->reward );

        $product            =   Product::withStockEnabled()->get()->random();
        $unit               =   $product->unit_quantities()->where( 'quantity', '>', 0 )->first();
        $product_price      =   $this->faker->numberBetween( $rules->first()->from, $rules->first()->to );
        $subtotal           =   $product_price;
        $shippingFees       =   0;

        $customer           =   Customer::first();

        if ( ! $customer->group->reward instanceof RewardSystem ) {
            $customer->group->reward_system_id  =   $reward->id;
            $customer->group->save();
        }

        $previousCoupons    =   $customer->coupons()->count();

        for( $i = 0; $i < $timesForOrders; $i++ ) {
            $response   =   $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/orders', [
                    'customer_id'           =>  $customer->id,
                    'type'                  =>  [ 'identifier' => 'takeaway' ],
                    // 'discount_type'         =>  'percentage',
                    // 'discount_percentage'   =>  2.5,
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
                            'unit_price'            =>  $product_price,
                            'unit_quantity_id'      =>  $unit->id,
                            'custom'                =>  'retail'
                        ], 
                    ],
                    'payments'              =>  [
                        [
                            'identifier'    =>  'paypal-payment',
                            'value'         =>  ns()->currency->define( $subtotal )
                                ->additionateBy( $shippingFees )
                                ->getRaw()
                        ]
                    ]
                ]);

            $response->assertStatus( 200 );
        }

        $currentCoupons    =   $customer->coupons()->count();
        
        $response->assertJsonPath( 'data.order.payment_status', Order::PAYMENT_PAID );
        $this->assertTrue( $previousCoupons < $currentCoupons, __( 'The coupons count has\'nt changed.' ) );
    }

    protected function attemptCouponUsage()
    {
        /**
         * We'll try to see if a coupon
         * has been issued by the end of this reward
         */
        $faker              =   Factory::create();
        $customerCoupon     =   CustomerCoupon::where( 'customer_id', '!=', 0 )->get()->last();

        $customer           =   $customerCoupon->customer;
        $products           =   $this->retreiveProducts();
        $shippingFees       =   0;
        $subtotal           =   $products->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();


        if ( $customerCoupon instanceof CustomerCoupon ) {
            $allCoupons         =   [
                [
                    'customer_coupon_id'    =>  $customerCoupon->id,
                    'coupon_id'             =>  $customerCoupon->coupon_id,
                    'name'                  =>  $customerCoupon->name,
                    'type'                  =>  'percentage_discount',
                    'code'                  =>  $customerCoupon->code,
                    'limit_usage'           =>  $customerCoupon->coupon->limit_usage,
                    'value'                 =>  ns()->currency->define( $customerCoupon->coupon->discount_value )
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

        $dateString         =   ns()->date->startOfDay()->addHours( 
            $faker->numberBetween( 0,23 ) 
        )->format( 'Y-m-d H:m:s' );

        $orderData  =   [
            'customer_id'           =>  $customer->id,
            'type'                  =>  [ 'identifier' => 'takeaway' ],
            'discount_type'         =>  $discount[ 'type' ],
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
            'payments'              =>  [
                [
                    'identifier'    =>  'cash-payment',
                    'value'         =>  ns()->currency->define( ( $subtotal + $shippingFees ) - $totalCoupons )
                        ->getRaw()
                ]
            ]
        ];

        $response   =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $orderData );
        
        
        $response->assertJson([
            'status'    =>  'success'
        ]);

        /**
         * check if coupon usage has been updated.
         */
        $oldUsage   =   $customerCoupon->usage;
        $customerCoupon->refresh();

        $this->assertTrue( $oldUsage !== $customerCoupon->usage, __( 'The coupon usage hasn\'t been updated.' ) );
    }

    private function retreiveProducts()
    {
        $products       =   Product::with( 'unit_quantities' )->get()->shuffle()->take(3);

        return $products->map( function( $product ) {
            $unitElement    =   $this->faker->randomElement( $product->unit_quantities );

            $data           =   [
                'name'                  =>  'Fees',
                'quantity'              =>  $this->faker->numberBetween(1,10),
                'unit_price'            =>  $unitElement->sale_price,
                'tax_type'              =>  'inclusive',
                'tax_group_id'          =>  1,
                'unit_id'               =>  $unitElement->unit_id,
            ];

            if ( $this->faker->randomElement([ false, true ]) ) {
                $data[ 'product_id' ]       =   $product->id;
                $data[ 'unit_quantity_id' ] =   $unitElement->id;
            }

            return $data;
        })->filter( function( $product ) {
            return $product[ 'quantity' ] > 0;
        });
    }
}