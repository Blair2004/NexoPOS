<?php

namespace Tests\Traits;

use App\Classes\Currency;
use App\Exceptions\NotAllowedException;
use App\Models\AccountType;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\CustomerAccountHistory;
use App\Models\CustomerCoupon;
use App\Models\Order;
use App\Models\OrderInstalment;
use App\Models\OrderPayment;
use App\Models\OrderProduct;
use App\Models\OrderProductRefund;
use App\Models\PaymentType;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Models\ProductSubItem;
use App\Models\ProductUnitQuantity;
use App\Models\Register;
use App\Models\RegisterHistory;
use App\Models\RewardSystem;
use App\Models\TaxGroup;
use App\Models\Unit;
use App\Models\User;
use App\Services\CashRegistersService;
use App\Services\CurrencyService;
use App\Services\CustomerService;
use App\Services\OrdersService;
use App\Services\ProductService;
use App\Services\TaxService;
use App\Services\TestService;
use App\Services\UnitService;
use Exception;
use Faker\Factory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;

trait WithOrderTest
{
    use WithFaker;

    protected $customProductParams = [];

    protected $customOrderParams = [];

    protected $processCoupon = true;

    protected $useDiscount = true;

    protected $shouldRefund = false;

    protected $customDate = true;

    protected $shouldMakePayment = true;

    protected $count = 1;

    protected $totalDaysInterval = 1;

    protected $users = [];

    protected $defaultProcessing = true;

    protected $allowQuickProducts = true;

    protected function attemptPostOrder( $callback )
    {
        $faker = Factory::create();
        $responses = [];
        $startOfWeek = ns()->date->clone()->startOfWeek()->subDays($this->totalDaysInterval);

        for ( $i = 0; $i < $this->totalDaysInterval; $i++ ) {
            $date = $startOfWeek->addDay()->clone();
            $this->count = $this->count === false ? $faker->numberBetween(5, 10) : $this->count;
            $this->output( sprintf( "\e[32mWill generate for the day \"%s\", %s order(s)", $date->toFormattedDateString(), $this->count ) );
            $responses[] = $this->processOrders( $date, $callback );
        }

        return $responses;
    }

    protected function attemptCreateOrderOnRegister( $data = [] )
    {
        RegisterHistory::truncate();

        Register::where( 'id', '>', 0 )->update([
            'balance' => 0,
        ]);

        $cashRegister = Register::first();

        $previousValue = $cashRegister->balance;

        /**
         * @var CashRegistersService
         */
        $cashRegisterService = app()->make( CashRegistersService::class );

        /**
         * Just in case it's opened
         */
        try {
            $cashRegisterService->closeRegister( $cashRegister, 0, __( 'Attempt closing' ) );
            $cashRegister->refresh();
        } catch ( NotAllowedException $exception ) {
            // it's probably not opened, let's proceed...
        }

        $result = $cashRegisterService->openRegister( $cashRegister, 100, __( 'Opening the cash register' ) );

        $previousValue = (float) $result[ 'data' ][ 'history' ]->value;
        $specificMoment = ns()->date->now()->toDateTimeString();

        /**
         * Step 1 : let's prepare the order
         * before submitting that.
         */
        $response = $this->registerOrderForCashRegister( $cashRegister, $data[ 'orderData' ] ?? [] );

        /**
         * between each operation
         * we need to refresh the cash register
         */
        $cashRegister->refresh();

        /**
         * let's fetch all order that was created on that cash register
         * from a specific moment
         */
        $totalValue = ns()->currency->define( RegisterHistory::where( 'register_id', $cashRegister->id )
            ->whereIn( 'action', RegisterHistory::IN_ACTIONS )
            ->sum( 'value' ) )->getRaw();

        /**
         * We should also take into account the change that produce
         * records on the cash register history
         */
        $rawTotalChangeValue = RegisterHistory::where( 'action', RegisterHistory::ACTION_CHANGE )
            ->where( 'register_id', $cashRegister->id )
            ->sum( 'value' );

        $totalChangeValue = ns()->currency->define( $rawTotalChangeValue )->getRaw();

        /**
         * only if the order total is greater than 0
         */
        if ( (float) $response[ 'data' ][ 'order' ][ 'tendered' ] > 0 ) {
            $this->assertNotEquals( $cashRegister->balance, $previousValue, __( 'There hasn\'t been any change during the transaction on the cash register balance.' ) );
            $this->assertEquals( (float) $cashRegister->balance, (float) ( ns()->currency->define( $totalValue )->subtractBy( $totalChangeValue )->getRaw() ), __( 'The cash register balance hasn\'t been updated correctly.' ) );
        }

        /**
         * let's update tha value for making
         * accurate comparisons.
         */
        $previousValue = (float) $cashRegister->balance;

        /**
         * let's assert only one history has been created
         * for the selected cash register.
         */
        $historyCount = RegisterHistory::where( 'register_id', $cashRegister->id )
            ->where( 'action', RegisterHistory::ACTION_SALE )
            ->count();

        $this->assertTrue( $historyCount == count( $response[ 'data' ][ 'order' ][ 'payments' ] ), 'The cash register history is not accurate' );

        /**
         * Step 2: We'll try here to delete order
         * from the register and see if the balance is updated
         */
        $this->createAndDeleteOrderFromRegister( $cashRegister, $data[ 'orderData' ] ?? [] );

        /**
         * between each operation
         * we need to refresh the cash register
         */
        $cashRegister->refresh();

        /**
         * let's update tha value for making
         * accurate comparisons.
         */
        $previousValue = (float) $cashRegister->balance;

        /**
         * Step 3 : disburse (cash-out) some cash
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
         * let's update tha value for making
         * accurate comparisons.
         */
        $previousValue = (float) $cashRegister->balance;

        /**
         * Step 4 : cash in some cash
         */
        $this->cashInOnRegister( $cashRegister, $cashRegisterService );

        /**
         * We neet to refresh the register
         * to make sure it has the updated values.
         */
        $cashRegister->refresh();

        $this->assertNotEquals( $cashRegister->balance, (float) $previousValue, __( 'There hasn\'t been any change during the transaction on the cash register balance.' ) );

        /**
         * Let's initialize the total transactions
         */
        $totalTransactions = 0;

        /**
         * last time the cash register has opened
         */
        $opening = RegisterHistory::action( RegisterHistory::ACTION_OPENING )->orderBy( 'id', 'desc' )->first();

        /**
         * We'll start by computing orders
         */
        $openingBalance = (float) $opening->value;

        $totalCashing = RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_CASHING )->sum( 'value' );

        $totalSales = RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_SALE )->sum( 'value' );

        $totalClosing = RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_CLOSING )->sum( 'value' );

        $totalCashOut = RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_CASHOUT )->sum( 'value' );

        $totalChange = RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_CHANGE )->sum( 'value' );

        $totalRefunds = RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_REFUND )->sum( 'value' );

        $totalDelete = RegisterHistory::register( $cashRegister )
            ->from( $opening->created_at )
            ->action( RegisterHistory::ACTION_DELETE )->sum( 'value' );

        $totalTransactions = ns()->currency->define( $openingBalance )
            ->additionateBy( $totalCashing )
            ->additionateBy( $totalSales )
            ->subtractBy( $totalClosing )
            ->subtractBy( $totalChange )
            ->subtractBy( $totalRefunds )
            ->subtractBy( $totalCashOut )
            ->subtractBy( $totalDelete )
            ->getRaw();

        $this->assertEquals(
            ns()->currency->getRaw( $cashRegister->balance ),
            $totalTransactions,
            __( 'The transaction aren\'t reflected on the register balance' )
        );

        return compact( 'response', 'cashRegister' );
    }

    public function attemptCreateAndEditOrderWithLowStock()
    {
        /**
         * @var ProductService $productSevice
         */
        $productService = app()->make( ProductService::class );

        /**
         * @var TestService $testService
         */
        $testService = app()->make( TestService::class );

        /**
         * Step 1: we'll set the quantity to be 3
         * and we'll create the order with 2 quantity partially paid
         */
        $product = Product::where( 'stock_management', Product::STOCK_MANAGEMENT_ENABLED )
            ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
            ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
            ->get()
            ->random();

        $productService->setQuantity( $product->id, $product->unit_quantities->first()->unit_id, 3 );

        /**
         * Let's prepare the order to submit that.
         */
        $orderDetails = $testService->prepareOrder(
            date: ns()->date->now(),
            config: [
                'allow_quick_products' => false,
                'payments' => function ( $details ) {
                    return [
                        [
                            'identifier' => 'cash-payment',
                            'value' => $details[ 'subtotal' ] / 3,
                        ],
                    ];
                },
                'products' => fn() => collect([
                    json_decode( json_encode([
                        'name' => $product->name,
                        'id' => $product->id,
                        'quantity' => 2,
                        'unit_price' => 10,
                        'unit_quantities' => [ $product->unit_quantities->first() ],
                    ]) ),
                ]),
            ]
        );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $orderDetails );

        /**
         * Step 2: Ensure no error occured
         */
        $response->assertStatus( 200 );

        $details = $response->json();

        /**
         * Step 3: update the order with the same product
         * and check if it goes through
         */
        $details[ 'data' ][ 'order' ][ 'type' ] = [ 'identifier' => $details[ 'data' ][ 'order' ][ 'type' ] ];
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $details[ 'data' ][ 'order' ][ 'id' ], $details[ 'data' ][ 'order' ] );

        $response->assertStatus(200, 'An error occured while submitting the order' );
    }

    public function attemptCreateAndEditOrderWithGreaterQuantity()
    {
        /**
         * @var ProductService $productSevice
         */
        $productService = app()->make( ProductService::class );

        /**
         * @var TestService $testService
         */
        $testService = app()->make( TestService::class );

        /**
         * Step 1: we'll set the quantity to be 3
         * and we'll create the order with 2 quantity partially paid
         */
        $product = Product::where( 'stock_management', Product::STOCK_MANAGEMENT_ENABLED )
            ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
            ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
            ->first();

        $productService->setQuantity( $product->id, $product->unit_quantities->first()->unit_id, 3 );

        /**
         * Let's prepare the order to submit that.
         */
        $orderDetails = $testService->prepareOrder(
            date: ns()->date->now(),
            config: [
                'allow_quick_products' => false,
                'payments' => function ( $details ) {
                    return [
                        [
                            'identifier' => 'cash-payment',
                            'value' => $details[ 'subtotal' ] / 3,
                        ],
                    ];
                },
                'products' => fn() => collect([
                    json_decode( json_encode([
                        'name' => $product->name,
                        'id' => $product->id,
                        'quantity' => 2,
                        'unit_price' => 10,
                        'unit_quantities' => [ $product->unit_quantities->first() ],
                    ]) ),
                ]),
            ]
        );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $orderDetails );

        /**
         * Step 2: Ensure no error occured
         */
        $response->assertStatus( 200 );

        $details = $response->json();

        /**
         * Step 3: update the order with the same product
         * and check if it goes through
         */
        $details[ 'data' ][ 'order' ][ 'type' ] = [ 'identifier' => $details[ 'data' ][ 'order' ][ 'type' ] ];

        /**
         * We'll here request more quantity that what is
         * available on the inventory. This request must fail.
         */
        $details[ 'data' ][ 'order' ][ 'products' ][0][ 'quantity' ] = 4;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $details[ 'data' ][ 'order' ][ 'id' ], $details[ 'data' ][ 'order' ] );

        $response->assertStatus(500, 'An error occured while submitting the order' );
    }

    public function attemptCreateOrderWithGroupedProducts( $data = [] )
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        /**
         * @var ProductService
         */
        $productService = app()->make( ProductService::class );

        /**
         * @var UnitService
         */
        $unitService = app()->make( UnitService::class );

        $product = Product::type( Product::TYPE_GROUPED )
            ->whereRelation( 'sub_items.product.unit_quantities', 'quantity', '>', 500 )
            ->with([ 'unit_quantities' ])
            ->with( 'sub_items.product.unit_quantities', function ( $query ) {
                $query->where( 'quantity', '>', 500 );
            })
            ->get()
            ->random();

        /**
         * We would like to store the current Quantity
         * and the quantity on the grouped product
         * for a better computation later
         */
        $quantities = $product->sub_items->mapWithKeys( function ( $value, $key ) use ( $productService, $unitService ) {
            $unit = $unitService->get( $value->unit_id );
            $group = $unitService->getGroups( $unit->group_id );
            $baseUnit = $unitService->getBaseUnit( $group );

            return [ $value->product_id . '-' . $value->unit_id => [
                'currentQuantity' => $productService->getQuantity(
                    product_id: $value->product_id,
                    unit_id: $value->unit_id
                ),
                'product_id' => $value->product_id,
                'unit' => $unit,
                'unitGroup' => $group,
                'baseUnit' => $baseUnit,
                'quantity' => $value->quantity,
            ] ];
        })->toArray();

        /**
         * Let's prepare the order to submit that.
         */
        $orderDetails = $testService->prepareOrder(
            date: ns()->date->now(),
            config: [
                'allow_quick_products' => false,
                'products' => fn() => collect([$product]),
            ]
        );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $orderDetails );

        /**
         * Step 0: Ensure no error occurred
         */
        if ( $response->status() !== 200 ) {
            $response->assertStatus( 200 );
        }

        /**
         * Let's convert the response for a
         * better computation.
         */
        $response = $response->json();

        $orderProductQuantity = $response[ 'data' ][ 'order' ][ 'products' ][0][ 'quantity' ];

        $query = ProductHistory::where( 'order_id', $response[ 'data' ][ 'order' ][ 'id' ] )
            ->whereIn( 'product_id', collect( $quantities )->map( fn( $quantity ) => $quantity[ 'product_id' ] )->toArray() );

        /**
         * Step 1: assert match on the items included with the history
         */
        $this->assertTrue( $query->count() === $product->sub_items->count(), 'Mismatch between the sold product and the included products.' );

        /**
         * Step 2: We'll check if an history is created for the parent products
         */
        collect( $response[ 'data' ][ 'order' ][ 'products' ] )->each( function ( $orderProduct ) use ( $response ) {
            $this->assertTrue(
                ProductHistory::where( 'order_id', $response[ 'data' ][ 'order' ][ 'id' ] )->where( 'product_id', $orderProduct[ 'id' ] )->first() instanceof ProductHistory,
                sprintf( 'There is no product history for the parent product %s', $orderProduct[ 'name' ] )
            );
        });

        /**
         * Step 3: assert valid deduction of quantities
         */
        foreach ( $query->get() as $productHistory ) {
            $savedQuantity = $quantities[ $productHistory->product_id . '-' . $productHistory->unit_id ];
            $orderProduct = OrderProduct::findOrFail( $productHistory->order_product_id );

            $finalQuantity = $productService->computeSubItemQuantity(
                subItemQuantity: $savedQuantity[ 'quantity' ],
                parentQuantity: $orderProductQuantity,
                parentUnit: Unit::find( $orderProduct->unit_id )
            );

            $actualQuantity = $productService->getQuantity(
                product_id: $productHistory->product_id,
                unit_id: $productHistory->unit_id
            );

            if ( ! (float) ( $savedQuantity[ 'currentQuantity' ] - ( $finalQuantity ) ) === (float) $actualQuantity ) {
                throw new Exception( 'Something went wrong' );
            }

            $this->assertTrue( (float) ( $savedQuantity[ 'currentQuantity' ] - ( $finalQuantity ) ) === (float) $actualQuantity, 'Quantity sold and recorded not matching' );
        }

        return $response;
    }

    public function attemptRefundOrderWithGroupedProducts()
    {
        /**
         * @var OrdersService $orderService
         */
        $orderService = app()->make( OrdersService::class );

        /**
         * @var ProductService $productService
         */
        $productService = app()->make( ProductService::class );

        /**
         * @var UnitService
         */
        $unitService = app()->make( UnitService::class );

        $lastOrder = Order::orderBy( 'id', 'desc' )->first();

        $inventory = $lastOrder->products->map( function ( $orderProduct ) use ( $productService, $unitService ) {
            return $orderProduct->product->sub_items->mapWithKeys( function ( $subItem ) use ( $orderProduct, $productService, $unitService ) {
                $unit = $unitService->get( $subItem->unit_id );
                $unitGroup = $unitService->getGroups( $unit->group_id );
                $baseUnit = $unitService->getBaseUnit( $unitGroup );

                return [
                    $subItem->product_id . '-' . $subItem->unit_id => [
                        'currentQuantity' => $productService->getQuantity(
                            product_id: $subItem->product_id,
                            unit_id: $subItem->unit_id
                        ),
                        'unit' => $unit,
                        'parentUnit' => $unitService->get( $orderProduct->unit_id ),
                        'baseUnit' => $baseUnit,
                        'unitGroup' => $unitGroup,
                        'quantity' => $subItem->quantity,
                    ],
                ];
            });
        })->toArray();

        /**
         * Let's refund the order and see if the included products
         * are returned to the inventory.
         */
        $orderService->refundOrder(
            order: $lastOrder,
            fields: [
                'payment' => [
                    'identifier' => OrderPayment::PAYMENT_CASH,
                ],
                'products' => $lastOrder->products->map( function ( OrderProduct $product ) {
                    return [
                        'id' => $product->id,
                        'condition' => OrderProductRefund::CONDITION_UNSPOILED,
                        'description' => 'Returned from tests',
                        'quantity' => $product->quantity,
                        'unit_price' => $product->unit_price,
                    ];
                })->toArray(),
            ]
        );

        $lastOrder
            ->products()
            ->get()
            ->each( function ( OrderProduct $orderProduct, $index ) use ( $inventory, $productService ) {
                $entry = $inventory[ $index ];

                $orderProduct->product->sub_items->each( function ( $subItem ) use (  $entry, $orderProduct, $productService ) {
                    $savedQuantity = $entry[ $subItem->product_id . '-' . $subItem->unit_id ];

                    $finalQuantity = $productService->computeSubItemQuantity(
                        parentUnit: $savedQuantity[ 'parentUnit' ],
                        parentQuantity: $orderProduct->refunded_products->sum( 'quantity' ),
                        subItemQuantity: $savedQuantity[ 'quantity' ]
                    );

                    $actualQuantity = $productService->getQuantity(
                        product_id: $subItem->product_id,
                        unit_id: $subItem->unit_id
                    );

                    /**
                     * Step 1: Assert quantity is correctly updated
                     */
                    if ( ! (float) $actualQuantity === (float) ( $finalQuantity + $savedQuantity[ 'currentQuantity' ] ) ) {
                        throw new Exception( 'foo' );
                    }

                    $this->assertTrue(
                        (float) $actualQuantity === (float) ( $finalQuantity + $savedQuantity[ 'currentQuantity' ] ),
                        'The new quantity doesn\'t match.'
                    );
                });
            });
    }

    public function attemptUpdateOrderOnRegister()
    {
        /**
         * @var OrdersService $orderService
         */
        $orderService = app()->make( OrdersService::class );

        $result = $this->attemptCreateOrderOnRegister([
            'orderData' => [
                'payments' => [], // we'll disable payments.
            ],
        ]);

        extract( $result );
        /**
         * @var array $response
         * @var Register $cashRegister
         */
        $order = Order::find( $response[ 'data' ][ 'order' ][ 'id' ] );
        $orderService->makeOrderSinglePayment([
            'identifier' => OrderPayment::PAYMENT_CASH,
            'value' => $response[ 'data' ][ 'order' ][ 'total' ],
        ], $order );

        /**
         * Making assertions
         */
        $cashRegisterHistory = RegisterHistory::where( 'register_id', $cashRegister->id )->orderBy( 'id', 'desc' )->first();

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
        $testService = app()->make( TestService::class );

        $orderDetails = $testService->prepareOrder( ns()->date->now(), array_merge([
            'register_id' => $cashRegister->id,
        ], $data ) );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/orders', $orderDetails );

        $response->assertStatus( 200 );

        return $response = json_decode( $response->getContent(), true );
    }

    private function createAndDeleteOrderFromRegister( Register $cashRegister, $data )
    {
        /**
         * This test can't proceed without payments.
         */
        if ( isset( $data[ 'payments' ] ) && count( $data[ 'payments' ] ) === 0 ) {
            return false;
        }

        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );

        /**
         * @var OrdersService
         */
        $ordersService = app()->make( OrdersService::class );

        $orderDetails = $testService->prepareOrder( ns()->date->now(), array_merge([
            'register_id' => $cashRegister->id,
        ], $data ) );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $orderDetails );

        $response->assertStatus( 200 );

        $response = json_decode( $response->getContent(), true );

        $cashRegister->refresh();
        $previousValue = $cashRegister->balance;

        /**
         * Step 2: We'll attempt to delete the product
         * We should check if the register balance has changed.
         */
        $ordersService->deleteOrder( Order::find( $response[ 'data' ][ 'order' ][ 'id' ] ) );

        $cashRegister->refresh();

        $newAmount = ns()->currency->define( $previousValue )->subtractBy( $response[ 'data' ][ 'order' ][ 'total' ] )->getRaw();

        $this->assertEquals( (float) $cashRegister->balance, (float) $newAmount, 'The balance wasn\'t updated after deleting the order.' );

        return $response;
    }

    /**
     * Will disburse the cash register
     *
     * @param CashRegistersService $cashRegisterService
     * @return void
     */
    private function disburseCashFromRegister( Register $cashRegister, CashRegistersService $cashRegistersService )
    {
        $cashRegistersService->cashOut( $cashRegister, $cashRegister->balance / 1.5, __( 'Test disbursing the cash register' ) );
    }

    /**
     * Will disburse the cash register
     *
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
        $currency = app()->make( CurrencyService::class );
        $product = Product::withStockEnabled()
            ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
            ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
            ->get()
            ->random();
        $unitQuantity = $product->unit_quantities()->where( 'quantity', '>', 100 )->first();
        $subtotal = $unitQuantity->sale_price * 5;
        $shippingFees = 150;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id' => Customer::first()->id,
                'type' => [ 'identifier' => 'takeaway' ],
                'discount_type' => 'percentage',
                'discount_percentage' => 2.5,
                'addresses' => [
                    'shipping' => [
                        'name' => 'First Name Delivery',
                        'surname' => 'Surname',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'name' => 'EBENE Voundi',
                        'surname' => 'Antony Hervé',
                        'country' => 'United State Seattle',
                    ],
                ],
                'subtotal' => $subtotal,
                'shipping' => $shippingFees,
                'products' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price' => 12,
                        'unit_quantity_id' => $unitQuantity->id,
                    ],
                ],
                'payments' => [
                    [
                        'identifier' => 'paypal-payment',
                        'value' => $currency->define( $subtotal )
                            ->additionateBy( $shippingFees )
                            ->getRaw(),
                    ],
                ],
            ]);

        $response->assertJsonPath( 'data.order.payment_status', Order::PAYMENT_PAID );
        $response = json_decode( $response->getContent(), true );
        $this->assertTrue( $response[ 'data' ][ 'order' ][ 'payments' ][0][ 'identifier' ] === 'paypal-payment', 'Invalid payment identifier detected.' );

        return $response;
    }

    protected function attemptCreateOrderPaidWithCustomerBalance()
    {
        /**
         * Next we'll create the order assigning
         * the order type we have just created
         */
        $currency = app()->make( CurrencyService::class );

        $product = Product::where( 'type', '<>', Product::TYPE_GROUPED )
            ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
            ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
            ->withStockEnabled()
            ->get()
            ->random();
        $unit = $product->unit_quantities()->where( 'quantity', '>', 0 )->first();
        $subtotal = $unit->sale_price * 5;
        $shippingFees = 150;

        /**
         * @var CustomerService
         */
        $customerService = app()->make( CustomerService::class );
        $customer = Customer::first();

        /**
         * we'll try crediting customer account
         */
        $oldBalance = $customer->account_amount;
        $customerService->saveTransaction( $customer, CustomerAccountHistory::OPERATION_ADD, $subtotal + $shippingFees, 'For testing purpose...' );
        $customer->refresh();

        /**
         * #Test: We'll check if the old balance is different
         * from the new one. Meaning the change was effective.
         */
        $this->assertTrue( (float) $oldBalance < (float) $customer->account_amount, 'The customer account hasn\'t been updated.' );
        $oldBalance = (float) $customer->account_amount;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id' => $customer->id,
                'type' => [ 'identifier' => 'takeaway' ],
                'discount_type' => 'percentage',
                'discount_percentage' => 2.5,
                'addresses' => [
                    'shipping' => [
                        'name' => 'First Name Delivery',
                        'surname' => 'Surname',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'name' => 'EBENE Voundi',
                        'surname' => 'Antony Hervé',
                        'country' => 'United State Seattle',
                    ],
                ],
                'subtotal' => $subtotal,
                'shipping' => $shippingFees,
                'products' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price' => 12,
                        'unit_quantity_id' => $unit->id,
                    ],
                ],
                'payments' => [
                    [
                        'identifier' => OrderPayment::PAYMENT_ACCOUNT,
                        'value' => $currency->define( $subtotal )
                            ->additionateBy( $shippingFees )
                            ->getRaw(),
                    ],
                ],
            ]);

        $response->assertJsonPath( 'data.order.payment_status', Order::PAYMENT_PAID );
        $response = json_decode( $response->getContent(), true );

        $this->assertTrue( $response[ 'data' ][ 'order' ][ 'payments' ][0][ 'identifier' ] === OrderPayment::PAYMENT_ACCOUNT, 'Invalid payment identifier detected.' );

        /**
         * Let's check if after the sale the customer balance has been updated.
         */
        $customer->refresh();
        $this->assertTrue( $oldBalance > (float) $customer->account_amount, 'The account has been updated' );

        /**
         * let's check if there is a stock flow transaction
         * that record the customer payment.
         */
        $history = CustomerAccountHistory::where( 'customer_id', $customer->id )
            ->where( 'operation', CustomerAccountHistory::OPERATION_PAYMENT )
            ->orderBy( 'id', 'desc' )
            ->first();

        $this->assertTrue( (float) $history->amount === (float) $subtotal + $shippingFees, 'The customer account history transaction is not valid.' );

        $cashFlow = CashFlow::where( 'customer_account_history_id', $history->id )
            ->operation( CashFlow::OPERATION_DEBIT )
            ->first();

        $this->assertTrue( $cashFlow instanceof CashFlow, 'No cash flow were found after the customer account payment.' );
    }

    protected function attemptCreateCustomPaymentType()
    {
        /**
         * To avoid any error, we'll make sure to delete the
         * payment type if that already exists.
         */
        $paymentType = PaymentType::identifier( 'paypal-payment' )->first();

        if ( $paymentType instanceof PaymentType ) {
            $paymentType->delete();
        }

        /**
         * First we'll create a custom payment type.
         * that has paypal as identifier
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/crud/ns.payments-types', [
                'label' => __( 'PayPal' ),
                'general' => [
                    'identifier' => 'paypal-payment',
                    'active' => true,
                    'priority' => 0,
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
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
        $responses = [];
        /**
         * @var CurrencyService
         */
        $currency = app()->make( CurrencyService::class );
        $faker = Factory::create();

        /**
         * @var TaxService
         */
        $taxService = app()->make( TaxService::class );

        for ( $i = 0; $i < $this->count; $i++ ) {
            $singleResponse = [];

            $products = Product::where( 'type', '<>', Product::TYPE_GROUPED )
                ->whereRelation( 'unit_quantities', 'quantity', '>', 1000 )
                ->with( 'unit_quantities', function ( $query ) {
                    $query->where( 'quantity', '>', 100 );
                })
                ->get()
                ->shuffle()
                ->take(3);
            $shippingFees = $faker->randomElement([10, 15, 20, 25, 30, 35, 40]);
            $discountRate = $faker->numberBetween(0, 5);

            $products = $products->map( function ( $product ) use ( $faker, $taxService ) {
                $unitElement = $faker->randomElement( $product->unit_quantities );
                $discountRate = 10;
                $quantity = $faker->numberBetween(1, 5);
                $data = array_merge([
                    'name' => $product->name,
                    'discount' => $taxService->getPercentageOf( $unitElement->sale_price * $quantity, $discountRate ),
                    'discount_percentage' => $discountRate,
                    'discount_type' => $faker->randomElement([ 'flat', 'percentage' ]),
                    'quantity' => $quantity,
                    'unit_price' => $unitElement->sale_price,
                    'tax_type' => 'inclusive',
                    'tax_group_id' => 1,
                    'unit_id' => $unitElement->unit_id,
                ], $this->customProductParams );

                if ( ! $this->allowQuickProducts ) {
                    $data[ 'product_id' ] = $product->id;
                    $data[ 'unit_quantity_id' ] = $unitElement->id;
                } elseif ( $faker->randomElement([ true, false ]) ) {
                    $data[ 'product_id' ] = $product->id;
                    $data[ 'unit_quantity_id' ] = $unitElement->id;
                }

                return $data;
            })->filter( function ( $product ) {
                return $product[ 'quantity' ] > 0;
            });

            /**
             * testing customer balance
             */
            $customer = Customer::get()->random();

            $subtotal = ns()->currency->getRaw( $products->map( function ( $product ) use ($currency) {
                $productSubTotal = $currency
                    ->fresh( $product[ 'unit_price' ] )
                    ->multiplyBy( $product[ 'quantity' ] )
                    ->subtractBy( $product[ 'discount' ] )
                    ->getRaw();

                return $productSubTotal;
            })->sum() );

            $customerCoupon = CustomerCoupon::get()->last();

            if ( $customerCoupon instanceof CustomerCoupon && $this->processCoupon && $customerCoupon->usage < $customerCoupon->limit_usage ) {
                $allCoupons = [
                    [
                        'customer_coupon_id' => $customerCoupon->id,
                        'coupon_id' => $customerCoupon->coupon_id,
                        'name' => $customerCoupon->name,
                        'type' => 'percentage_discount',
                        'code' => $customerCoupon->code,
                        'limit_usage' => $customerCoupon->coupon->limit_usage,
                        'value' => $currency->define( $customerCoupon->coupon->discount_value )
                            ->multiplyBy( $subtotal )
                            ->divideBy( 100 )
                            ->getRaw(),
                        'discount_value' => $customerCoupon->coupon->discount_value,
                        'minimum_cart_value' => $customerCoupon->coupon->minimum_cart_value,
                        'maximum_cart_value' => $customerCoupon->coupon->maximum_cart_value,
                    ],
                ];

                $totalCoupons = collect( $allCoupons )->map( fn( $coupon ) => $coupon[ 'value' ] )->sum();
            } else {
                $allCoupons = [];
                $totalCoupons = 0;
            }

            $discount = [
                'type' => '',
                'rate' => 0,
            ];

            $discountCoupons = 0;

            if ( $this->useDiscount ) {
                /**
                 * If the discount is percentage or flat.
                 */
                if ( $discount[ 'type' ] === 'percentage' ) {
                    $discount[ 'rate' ] = $discountRate;
                    $discount[ 'value' ] = $currency->define( $discount[ 'rate' ] )
                        ->multiplyBy( $subtotal )
                        ->divideBy( 100 )
                        ->getRaw();
                } else {
                    $discount[ 'value' ] = Currency::fresh( $subtotal )
                        ->divideBy( 2 )
                        ->getRaw();

                    $discount[ 'rate' ] = 0;
                }

                $discountCoupons = $currency->define( $discount[ 'value' ] )
                    ->additionateBy( $allCoupons[0][ 'value' ] ?? 0 )
                    ->getRaw();
            }

            $dateString = $currentDate->startOfDay()->addHours(
                $faker->numberBetween( 0, 23 )
            )->format( 'Y-m-d H:m:s' );

            $orderData = array_merge([
                'customer_id' => $customer->id,
                'type' => [ 'identifier' => 'takeaway' ],
                'discount_type' => $discount[ 'type' ],
                'created_at' => $this->customDate ? $dateString : null,
                'discount_percentage' => $discount[ 'rate' ] ?? 0,
                'discount' => $discount[ 'value' ] ?? 0,
                'addresses' => [
                    'shipping' => [
                        'name' => 'First Name Delivery',
                        'surname' => 'Surname',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'name' => 'EBENE Voundi',
                        'surname' => 'Antony Hervé',
                        'country' => 'United State Seattle',
                    ],
                ],
                'author' => ! empty( $this->users ) // we want to randomise the users
                    ? collect( $this->users )->suffle()->first()
                    : User::get( 'id' )->pluck( 'id' )->shuffle()->first(),
                'coupons' => $allCoupons,
                'subtotal' => $subtotal,
                'shipping' => $shippingFees,
                'products' => $products->toArray(),
                'payments' => $this->shouldMakePayment ? [
                    [
                        'identifier' => 'cash-payment',
                        'value' => $currency->define( $subtotal )
                            ->additionateBy( $shippingFees )
                            ->subtractBy(
                                $discountCoupons
                            )
                            ->getRaw(),
                    ],
                ] : [],
            ], $this->customOrderParams );

            $customer = Customer::find( $orderData[ 'customer_id' ] );
            $customerFirstPurchases = $customer->purchases_amount;
            $customerFirstOwed = $customer->owed_amount;

            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/orders', $orderData );

            $response->assertJson([
                'status' => 'success',
            ]);

            $singleResponse[ 'order-creation' ] = json_decode( $response->getContent(), true );

            if ( $this->shouldMakePayment ) {
                $netsubtotal = $currency
                    ->define( $orderData[ 'subtotal' ] )
                    ->subtractBy( $totalCoupons )
                    ->subtractBy( $orderData[ 'discount' ] )
                    ->getRaw();

                $total = $currency->define( $netsubtotal )
                    ->additionateBy( $orderData[ 'shipping' ] )
                    ->getRaw();

                $this->assertEquals( $currency->getRaw(
                    Arr::get( $singleResponse[ 'order-creation' ], 'data.order.subtotal' )
                ), $currency->getRaw( $orderData[ 'subtotal' ] ) );

                $this->assertEquals( $currency->getRaw(
                    Arr::get( $singleResponse[ 'order-creation' ], 'data.order.total' )
                ), $currency->define( $netsubtotal )
                    ->additionateBy( $orderData[ 'shipping' ] )
                    ->getRaw()
                );

                $couponValue = ( ! empty( $orderData[ 'coupons' ] ) ? (float) $orderData[ 'coupons' ][0][ 'value' ] : 0 );
                $totalPayments = collect( $orderData[ 'payments' ] )->map( fn( $payment ) => (float) $payment[ 'value' ] )->sum() ?: 0;
                $sum = (  (float) $orderData[ 'subtotal' ] + (float) $orderData[ 'shipping' ] - (float) $orderData[ 'discount' ] - $couponValue );
                $change = ns()->currency->fresh( $totalPayments )->subtractBy( $sum )->getRaw();

                $changeFromOrder = ns()->currency->getRaw( Arr::get( $singleResponse[ 'order-creation' ], 'data.order.change' ) );
                $this->assertEquals( $changeFromOrder, $change );

                $singleResponse[ 'order-payment' ] = json_decode( $response->getContent() );

                /**
                 * test if the order has updated
                 * correctly the customer account
                 */
                $customer->refresh();
                $customerSecondPurchases = $customer->purchases_amount;
                $customerSecondOwed = $customer->owed_amount;

                if ( (float) trim( $customerFirstPurchases + ( $total ?? 0 ) ) != (float) trim( $customerSecondPurchases ) ) {
                    throw new Exception(
                        sprintf(
                            __( 'The customer purchase hasn\'t been updated. Expected %s Current Value %s. Total : %s' ),
                            $customerFirstPurchases + $total,
                            $customerSecondPurchases,
                            $total
                        )
                    );
                }
            }

            $responseData = json_decode( $response->getContent(), true );

            /**
             * Let's test whether the cash
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
                $callback( $response, $responseData );
            }

            if ( $faker->randomElement([ true ]) === true && $this->shouldRefund ) {
                /**
                 * We'll keep original products amounts and quantity
                 * this means we're doing a full refund of price and quantities
                 */
                $productCondition = $faker->randomElement([
                    OrderProductRefund::CONDITION_DAMAGED,
                    OrderProductRefund::CONDITION_UNSPOILED,
                ]);

                $products = collect( $responseData[ 'data' ][ 'order' ][ 'products' ] )->map( function ( $product ) use ( $faker, $productCondition ) {
                    return array_merge( $product, [
                        'condition' => $productCondition,
                        'description' => __( 'A random description from the refund test' ),
                        'quantity' => $faker->randomElement([
                            $product[ 'quantity' ],
                            // floor( $product[ 'quantity' ] / 2 )
                        ]),
                    ]);
                });

                $response = $this->withSession( $this->app[ 'session' ]->all() )
                    ->json( 'POST', 'api/nexopos/v4/orders/' . $responseData[ 'data' ][ 'order' ][ 'id' ] . '/refund', [
                        'payment' => [
                            'identifier' => $faker->randomElement([
                                OrderPayment::PAYMENT_ACCOUNT,
                                OrderPayment::PAYMENT_CASH,
                            ]),
                        ],
                        'refund_shipping' => $faker->randomElement([ true, false ]),
                        'total' => collect( $products )
                            ->map( fn( $product ) => $currency
                                    ->define( $product[ 'quantity' ] )
                                    ->multiplyBy( $product[ 'unit_price' ] )
                                    ->getRaw()
                            )->sum(),
                        'products' => $products,
                    ]);

                $response->assertJson([
                    'status' => 'success',
                ]);

                /**
                 * A single cash flow should be
                 * created for that order for the sale account
                 */
                $totalCashFlow = CashFlow::where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
                    ->where( 'operation', CashFlow::OPERATION_CREDIT )
                    ->where( 'expense_category_id', ns()->option->get( 'ns_sales_cashflow_account' ) )
                    ->count();

                $this->assertTrue( $totalCashFlow === 1, 'More than 1 cash flow was created for the sale account.' );

                /**
                 * all refund transaction give a stock flow record.
                 * We need to check if it has been created.
                 */
                $totalRefundedCashFlow = CashFlow::where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
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
                    $totalSpoiledCashFlow = CashFlow::where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
                        ->where( 'operation', CashFlow::OPERATION_DEBIT )
                        ->where( 'expense_category_id', ns()->option->get( 'ns_stock_return_spoiled_account' ) )
                        ->count();

                    $this->assertTrue( $totalSpoiledCashFlow === $products->count(), 'Not enough cash flow entry were created for the refunded product' );
                }

                $singleResponse[ 'order-refund' ] = json_decode( $response->getContent() );
            }

            $responses[] = $singleResponse;
        }

        return $responses;
    }

    protected function attemptOrderWithProductPriceMode()
    {
        $currency = app()->make( CurrencyService::class );
        $product = Product::withStockEnabled()
            ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
            ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
            ->get()
            ->random();
        $unit = $product->unit_quantities()->where( 'quantity', '>', 100 )->first();
        $subtotal = $unit->sale_price * 5;
        $shippingFees = 150;

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id' => 1,
                'type' => [ 'identifier' => 'takeaway' ],
                'discount_type' => 'percentage',
                'discount_percentage' => 2.5,
                'addresses' => [
                    'shipping' => [
                        'name' => 'First Name Delivery',
                        'surname' => 'Surname',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'name' => 'EBENE Voundi',
                        'surname' => 'Antony Hervé',
                        'country' => 'United State Seattle',
                    ],
                ],
                'subtotal' => $subtotal,
                'shipping' => $shippingFees,
                'products' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price' => 8.5,
                        'unit_quantity_id' => $unit->id,
                        'mode' => 'retail',
                    ], [
                        'product_id' => $product->id,
                        'quantity' => 1,
                        'unit_price' => 8.5,
                        'unit_quantity_id' => $unit->id,
                        'mode' => 'normal',
                    ],
                ],
                'payments' => [
                    [
                        'identifier' => 'paypal-payment',
                        'value' => $currency->define( $subtotal )
                            ->additionateBy( $shippingFees )
                            ->getRaw(),
                    ],
                ],
            ]);

        $response = json_decode( $response->getContent(), true );
        $order = $response[ 'data' ][ 'order' ];

        $this->assertTrue( $order[ 'products' ][0][ 'mode' ] === 'retail', 'Failed to assert the first product price mode is "retail"' );
        $this->assertTrue( $order[ 'products' ][1][ 'mode' ] === 'normal', 'Failed to assert the second product price mode is "normal"' );
    }

    protected function attemptHoldAndCheckoutOrder()
    {
        /**
         * @var ProductService $productService
         */
        $productService = app()->make( ProductService::class );
        $result = $this->attemptCreateHoldOrder();

        /**
         * Step: 1 From this moment, the stock shouldn't  have changed.
         * So we'll check if there has been any stock change here.
         */
        $order = $result[ 'data' ][ 'order' ];

        /**
         * Step 2: From here, we'll make a payment to the order,
         * and make sure there is a stock deducted. First we'll keep
         * the actual products stock
         */
        $stock = collect( $order[ 'products' ] )->mapWithKeys( function ( $orderProduct ) use ( $productService ) {
            return [
                $orderProduct[ 'id' ] => $productService->getQuantity( $orderProduct[ 'product_id' ], $orderProduct[ 'unit_id' ] ),
            ];
        });

        $order[ 'type' ] = [ 'identifier' => $order[ 'type' ] ];
        $order[ 'products' ] = collect( $order[ 'products' ] )->map( function ( $product ) {
            $product[ 'quantity' ] = 5; // we remove 1 quantity so it returns to inventory

            return $product;
        })->toArray();

        $order[ 'payments' ][] = [
            'identifier' => 'cash-payment',
            'value' => abs( $order[ 'change' ] ),
        ];

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $order[ 'id' ], $order );

        $response->assertStatus(200);

        $response->assertJsonPath( 'data.order.payment_status', Order::PAYMENT_PAID );

        $stock->each( function ( $quantity, $orderProductID ) use ( $productService ) {
            $orderProduct = OrderProduct::find( $orderProductID );
            $newQuantity = $productService->getQuantity( $orderProduct->product_id, $orderProduct->unit_id );

            $this->assertTrue(
                $newQuantity < $quantity,
                sprintf(
                    __( 'Stock mismatch! %s was expected to be greater than %s, after an order update' ),
                    $newQuantity,
                    $quantity
                )
            );
        });
    }

    protected function attemptHoldOrderAndCheckoutWithGroupedProducts()
    {
        /**
         * @var ProductService $productService
         */
        $productService = app()->make( ProductService::class );

        $product = Product::withStockEnabled()
            ->where( 'type', Product::TYPE_GROUPED )
            ->with([ 'unit_quantities' ])
            ->get()
            ->random();

        /**
         * Step 1: We want to make sure the system take in account
         * the remaining quantity while editing the order.
         */
        $product->sub_items()->with([ 'unit_quantity', 'product' ])->get()->each( function ( ProductSubItem $subProduct ) use ( $productService ) {
            $productService->setQuantity( $subProduct->product->id, $subProduct->unit_quantity->unit_id, 25000 );
        });

        $unitQuantity = $product->unit_quantities->first();

        if ( ! $unitQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( 'No valid unit is available.' );
        }

        $subtotal = $unitQuantity->sale_price * 5;
        $orderDetails = [
            'customer_id' => 1,
            'type' => [ 'identifier' => 'takeaway' ],
            'discount_type' => 'percentage',
            'discount_percentage' => 2.5,
            'addresses' => [
                'shipping' => [
                    'name' => 'First Name Delivery',
                    'surname' => 'Surname',
                    'country' => 'Cameroon',
                ],
                'billing' => [
                    'name' => 'EBENE Voundi',
                    'surname' => 'Antony Hervé',
                    'country' => 'United State Seattle',
                ],
            ],
            'payment_status' => Order::PAYMENT_HOLD,
            'subtotal' => $subtotal,
            'shipping' => 150,
            'products' => [
                [
                    'product_id' => $unitQuantity->product->id,
                    'quantity' => 3,
                    'unit_price' => 12,
                    'unit_quantity_id' => $unitQuantity->id,
                ],
            ],
        ];

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $orderDetails );

        $response->assertStatus(200);
        $response->assertJsonPath( 'data.order.payment_status', Order::PAYMENT_HOLD );

        $payment = PaymentType::first();
        $order = $response->json()[ 'data' ][ 'order' ];

        /**
         * Step 2: From here, we'll make a first payment to the order,
         * and make sure there is a stock deducted. First we'll keep
         * the actual products stock
         */
        $stock = collect( $order[ 'products' ] )->mapWithKeys( function ( $orderProduct ) use ( $productService ) {
            $product = Product::with([ 'sub_items.product', 'sub_items.unit_quantity' ])
                ->where( 'id', $orderProduct[ 'product_id' ] )
                ->first();

            return [
                $orderProduct[ 'id' ] => $product->sub_items->mapWithKeys( fn( $subItem ) => [
                    $subItem->id => $productService->getQuantity( $subItem->product->id, $subItem->unit_quantity->unit_id ),
                ]),
            ];
        });

        /**
         * Step 3: We'll try to make a payment to the order to
         * turn that into a partially paid order.
         */
        $orderDetails = array_merge( $orderDetails, [
            'products' => Order::find( $order[ 'id' ] )
                ->products()
                ->get()
                ->map( function ( $product ) {
                    $product->quantity = 4;

                    return $product;
                })
                ->toArray(),
            'payments' => [
                [
                    'identifier' => $payment->identifier,
                    'value' => $order[ 'total' ] / 3,
                ],
            ],
        ]);

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $order[ 'id' ], $orderDetails );

        $response->assertStatus(200);
        $response->assertJsonPath( 'data.order.payment_status', Order::PAYMENT_PARTIALLY );

        $stock->each( function ( $products, $parentProductID ) use ( $productService ) {
            $products->each( function ( $quantity, $subItemID ) use ( $productService ) {
                $productSubItem = ProductSubItem::with( 'product' )->find( $subItemID );
                $newQuantity = $productService->getQuantity( $productSubItem->product->id, $productSubItem->unit_id );
                $this->assertTrue( $newQuantity < $quantity, __( 'The quantity hasn\'t changed after selling a previously hold order.' ) );
            });
        });

        $this->assertTrue(
            ProductHistory::where( 'order_id', $order[ 'id' ] )->count() > 0,
            __( 'There has not been a stock transaction for an order that has partially received a payment.' )
        );

        return $response->json();
    }

    protected function attemptCreateHoldOrder()
    {
        /**
         * @var ProductService $productService
         */
        $productService = app()->make( ProductService::class );

        $product = Product::withStockEnabled()
            ->where( 'type', '!=', Product::TYPE_GROUPED )
            ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 0 ) )
            ->get()
            ->random();

        $unitQuantity = $product->unit_quantities->first();

        if ( ! $unitQuantity instanceof ProductUnitQuantity ) {
            throw new Exception( 'No valid unit is available.' );
        }

        /**
         * Step 1: We want to make sure the system take in account
         * the remaining quantity while editing the order.
         */
        $unitQuantity->quantity = 5;
        $unitQuantity->save();

        $subtotal = $unitQuantity->sale_price * 5;
        $orderDetails = [
            'customer_id' => 1,
            'type' => [ 'identifier' => 'takeaway' ],
            'discount_type' => 'percentage',
            'discount_percentage' => 2.5,
            'addresses' => [
                'shipping' => [
                    'name' => 'First Name Delivery',
                    'surname' => 'Surname',
                    'country' => 'Cameroon',
                ],
                'billing' => [
                    'name' => 'EBENE Voundi',
                    'surname' => 'Antony Hervé',
                    'country' => 'United State Seattle',
                ],
            ],
            'payment_status' => Order::PAYMENT_HOLD,
            'subtotal' => $subtotal,
            'shipping' => 150,
            'products' => [
                [
                    'product_id' => $unitQuantity->product->id,
                    'quantity' => 3,
                    'unit_price' => 12,
                    'unit_quantity_id' => $unitQuantity->id,
                ],
            ],
        ];

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $orderDetails );

        $response->assertStatus(200);
        $response->assertJsonPath( 'data.order.payment_status', Order::PAYMENT_HOLD );

        $payment = PaymentType::first();
        $order = $response->json()[ 'data' ][ 'order' ];

        /**
         * Step 2: From here, we'll make a first payment to the order,
         * and make sure there is a stock deducted. First we'll keep
         * the actual products stock
         */
        $stock = collect( $order[ 'products' ] )->mapWithKeys( function ( $orderProduct ) use ( $productService ) {
            return [
                $orderProduct[ 'id' ] => $productService->getQuantity( $orderProduct[ 'product_id' ], $orderProduct[ 'unit_id' ] ),
            ];
        });

        /**
         * Step 3: We'll try to make a payment to the order to
         * turn that into a partially paid order.
         */
        $orderDetails = array_merge( $orderDetails, [
            'products' => Order::find( $order[ 'id' ] )
                ->products()
                ->get()
                ->map( function ( $product ) {
                    $product->quantity = 4; // we assume the remaining stock has at least 1 quantity remaining.

                    return $product;
                })
                ->toArray(),
            'payments' => [
                [
                    'identifier' => $payment->identifier,
                    'value' => $order[ 'total' ] / 3,
                ],
            ],
        ]);

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $order[ 'id' ], $orderDetails );

        $response->assertStatus(200);
        $response->assertJsonPath( 'data.order.payment_status', Order::PAYMENT_PARTIALLY );

        $stock->each( function ( $quantity, $orderProductID ) use ( $productService ) {
            $orderProduct = OrderProduct::find( $orderProductID );
            $newQuantity = $productService->getQuantity( $orderProduct->product_id, $orderProduct->unit_id );
            $this->assertTrue( $newQuantity < $quantity, __( 'The quantity hasn\'t changed after selling a previously hold order.' ) );
        });

        $this->assertTrue(
            ProductHistory::where( 'order_id', $order[ 'id' ] )->count() > 0,
            __( 'There has not been a stock transaction for an order that has partially received a payment.' )
        );

        return $response->json();
    }

    protected function attemptDeleteOrder()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );
        $customer = Customer::get()->random();

        /**
         * We would like to check easilly
         * by reset the customer counter
         */
        $customer->purchases_amount = 0;
        $customer->save();

        $data = $testService->prepareOrder(
            orderDetails: [
                'customer_id' => $customer->id,
            ],
            config: [
                'products' => function () {
                    return Product::withStockEnabled()
                        ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
                        ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
                        ->where( 'type', '<>', Product::TYPE_GROUPED )
                        ->get();
                },
            ],
            date: ns()->date->now()
        );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $data );

        $response->assertStatus(200);

        $order = (object) json_decode( $response->getContent(), true )[ 'data' ][ 'order' ];
        $order = Order::with([ 'products', 'user' ])->find( $order->id );
        $refreshed = $customer->fresh();

        $this->assertTrue( $customer->purchases_amount < $refreshed->purchases_amount, 'The customer balance hasn\'t been updated.' );

        /**
         * @var ProductService
         */
        $productService = app()->make( ProductService::class );

        $products = $order->products
            ->filter( fn( $product ) => $product->product_id > 0 )
            ->map( function ( $product ) use ( $productService ) {
                $product->previous_quantity = $productService->getQuantity( $product->product_id, $product->unit_id );

                return $product;
            });

        /**
         * let's check if the order has a cash flow entry
         */
        $this->assertTrue( CashFlow::where( 'order_id', $order->id )->first() instanceof CashFlow, 'No cash flow created for the order.' );

        if ( $order instanceof Order ) {
            $order_id = $order->id;

            /**
             * @var OrdersService
             */
            $orderService = app()->make( OrdersService::class );
            $orderService->deleteOrder( $order );

            $totalPayments = OrderPayment::where( 'order_id', $order_id )->count();

            $this->assertTrue( $totalPayments === 0,
                sprintf(
                    __( 'An order payment hasn\'t been deleted along with the order (%s).' ),
                    $order->id
                )
            );

            /**
             * let's check if the purchase amount
             * for the customer has been updated accordingly
             */
            $newCustomer = $customer->fresh();

            $this->assertEquals( $newCustomer->purchases_amount, $customer->purchases_amount, 'The customer total purchase hasn\'t changed after deleting the order.' );

            /**
             * let's check if flow entry has been removed
             */
            $this->assertTrue( ! CashFlow::where( 'order_id', $order->id )->first() instanceof CashFlow, 'The cash flow hasn\'t been deleted.' );

            $products->each( function ( OrderProduct $orderProduct ) use ( $productService ) {
                $originalProduct = $orderProduct->product;

                /**
                 * Here we'll check if the stock returns when the
                 * order is deleted
                 */
                if ( $originalProduct->stock_management === Product::STOCK_MANAGEMENT_ENABLED ) {
                    $orderProduct->actual_quantity = $productService->getQuantity( $orderProduct->product_id, $orderProduct->unit_id );

                    /**
                     * Let's check if the quantity has been restored
                     * to the default value.
                     */
                    $this->assertTrue(
                        (float) $orderProduct->actual_quantity === (float) $orderProduct->previous_quantity + (float) $orderProduct->quantity,
                        __( 'The new quantity was not restored to what it was before the deletion.')
                    );
                }

                /**
                 * Here we'll check if there is an history created for every
                 * product that was deleted.
                 */
                $productHistory = ProductHistory::where( 'action', ProductHistory::ACTION_RETURNED )
                    ->where( 'order_product_id', $orderProduct->id )
                    ->where( 'order_id', $orderProduct->order_id )
                    ->first();

                $this->assertTrue(
                    ! $productHistory instanceof ProductHistory,
                    sprintf(
                        __( 'No product history was created for the product "%s" upon is was deleted with the order it\'s attached to.' ),
                        $orderProduct->name
                    )
                );
            });
        } else {
            throw new Exception( __( 'No order where found to perform the test.' ) );
        }
    }

    protected function attemptDeleteVoidedOrder()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );
        $customer = Customer::get()->random();

        /**
         * We would like to check easilly
         * by reset the customer counter
         */
        $customer->purchases_amount = 0;
        $customer->save();

        $data = $testService->prepareOrder(
            orderDetails: [
                'customer_id' => $customer->id,
            ],
            config: [
                'products' => function () {
                    return Product::where( 'STOCK_MANAGEMENT', Product::STOCK_MANAGEMENT_ENABLED )
                        ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
                        ->with( 'unit_quantities', function ( $query ) {
                            $query->where( 'quantity', '>', 100 );
                        })
                        ->where( 'type', '<>', Product::TYPE_GROUPED )
                        ->get();
                },
            ],
            date: ns()->date->now()
        );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $data );

        $orderData = (object) $response->json()[ 'data' ][ 'order' ];
        $order = Order::with([ 'products', 'user' ])->find( $orderData->id );

        /**
         * Step 1: Void an order before deleting
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $orderData->id . '/void', [
                'reason' => 'Testing Voiding',
            ]);

        $response->assertOk();

        $order->load([ 'products.product' ]);

        /**
         * We'll check if for each product on the order
         * there was a refund made for the returned goods
         */
        $order->products->each( function ( $product ) {
            // every product that aren't refund
            if ( $product->refunded_products()->count() === 0 && $product->product()->first() instanceof Product ) {
                $history = ProductHistory::where( 'operation_type', ProductHistory::ACTION_VOID_RETURN )
                    ->where( 'order_product_id', $product->id )
                    ->where( 'order_id', $product->order_id )
                    ->first();

                $this->assertTrue( $history instanceof ProductHistory, __( 'No return history was created for a void order product.' ) );
            }
        });

        $order->refresh();
        $order->load([ 'products.product' ]);

        /**
         * @var OrdersService
         */
        $orderService = app()->make( OrdersService::class );
        $orderService->deleteOrder( $order );

        $totalPayments = OrderPayment::where( 'order_id', $order->id )->count();

        $this->assertTrue( $totalPayments === 0,
            sprintf(
                __( 'An order payment hasn\'t been deleted along with the order (%s).' ),
                $order->id
            )
        );

        /**
         * Step 2: We'll now check if by deleting the order
         * we still have the product history created.
         */
        $order->products->each( function ( $product ) {
            // every product that aren't refund
            if ( $product->refunded_products()->count() === 0 ) {
                $history = ProductHistory::where( 'operation_type', ProductHistory::ACTION_RETURNED )
                    ->where( 'order_product_id', $product->id )
                    ->where( 'order_id', $product->order_id )
                    ->first();

                $this->assertTrue( ! $history instanceof ProductHistory, __( 'A stock return was performed while the order was initially voided.' ) );
            }
        });
    }

    protected function attemptVoidOrder()
    {
        /**
         * @var TestService
         */
        $testService = app()->make( TestService::class );
        $customer = Customer::get()->random();

        /**
         * We would like to check easilly
         * by reset the customer counter
         */
        $customer->purchases_amount = 0;
        $customer->save();

        $data = $testService->prepareOrder(
            orderDetails: [
                'customer_id' => $customer->id,
            ],
            config: [
                'products' => function () {
                    return Product::where( 'STOCK_MANAGEMENT', Product::STOCK_MANAGEMENT_ENABLED )
                        ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
                        ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
                        ->where( 'type', '<>', Product::TYPE_GROUPED )
                        ->get();
                },
            ],
            date: ns()->date->now()
        );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $data );

        $orderData = (object) $response->json()[ 'data' ][ 'order' ];
        $order = Order::with([ 'products', 'user' ])->find( $orderData->id );

        /**
         * Step 1: Void an order before deleting
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $orderData->id . '/void', [
                'reason' => 'Testing Voiding',
            ]);

        $order->load([ 'products.product' ]);

        /**
         * We'll check if for each product on the order
         * there was a refund made for the returned goods
         */
        $order->products->each( function ( $product ) {
            // every product that aren't refund
            if ( $product->refunded_products()->count() === 0 && $product->product()->first() instanceof Product ) {
                $history = ProductHistory::where( 'operation_type', ProductHistory::ACTION_VOID_RETURN )
                    ->where( 'order_product_id', $product->id )
                    ->where( 'order_id', $product->order_id )
                    ->first();

                $this->assertTrue( $history instanceof ProductHistory, __( 'No return history was created for a void order product.' ) );
            }
        });
    }

    protected function attemptRefundOrder( $productQuantity, $refundQuantity, $paymentStatus, $message )
    {
        /**
         * @var CurrencyService
         */
        $currency = app()->make( CurrencyService::class );

        $firstFetchCustomer = Customer::first();
        $firstFetchCustomer->save();

        $product = Product::withStockEnabled()
            ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
            ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
            ->first();

        $shippingFees = 150;
        $discountRate = 3.5;
        $products = [
            /**
             * this is a sample product/service
             */
            [
                'quantity' => $productQuantity,
                'unit_price' => $product->unit_quantities[0]->sale_price,
                'unit_quantity_id' => $product->unit_quantities[0]->id,
                'unit_id' => $product->unit_quantities[0]->unit_id,
            ],

            /**
             * An existing product
             */
            [
                'product_id' => $product->id,
                'quantity' => $productQuantity,
                'unit_price' => $product->unit_quantities[0]->sale_price,
                'unit_quantity_id' => $product->unit_quantities[0]->id,
                'unit_id' => $product->unit_quantities[0]->unit_id,
            ],
        ];

        $subtotal = collect( $products )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();
        $netTotal = $subtotal + $shippingFees;

        /**
         * We'll add taxes to the order in
         * case we have some tax group defined.
         */
        $taxes = [];
        $taxGroup = TaxGroup::first();
        if ( $taxGroup instanceof TaxGroup ) {
            $taxes = $taxGroup->taxes->map( function ( $tax ) {
                return [
                    'tax_name' => $tax->name,
                    'tax_id' => $tax->id,
                    'rate' => $tax->rate,
                ];
            });
        }

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id' => $firstFetchCustomer->id,
                'type' => [ 'identifier' => 'takeaway' ],
                'discount_type' => 'percentage',
                'discount_percentage' => $discountRate,
                'taxes' => $taxes,
                'tax_group_id' => $taxGroup->id,
                'tax_type' => 'inclusive',
                'addresses' => [
                    'shipping' => [
                        'name' => 'First Name Delivery',
                        'surname' => 'Surname',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'name' => 'EBENE Voundi',
                        'surname' => 'Antony Hervé',
                        'country' => 'United State Seattle',
                    ],
                ],
                'subtotal' => $subtotal,
                'shipping' => $shippingFees,
                'products' => $products,
                'payments' => [
                    [
                        'identifier' => OrderPayment::PAYMENT_CASH,
                        'value' => $netTotal,
                    ],
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        $responseData = json_decode( $response->getContent(), true );

        $secondFetchCustomer = $firstFetchCustomer->fresh();

        if ( $currency->define( $secondFetchCustomer->purchases_amount )
            ->subtractBy( $responseData[ 'data' ][ 'order' ][ 'total' ] )
            ->getRaw() != $currency->getRaw( $firstFetchCustomer->purchases_amount ) ) {
            throw new Exception(
                sprintf(
                    __( 'The purchase amount hasn\'t been updated correctly. Expected %s, got %s' ),
                    $secondFetchCustomer->purchases_amount - (float) $responseData[ 'data' ][ 'order' ][ 'total' ],
                    $firstFetchCustomer->purchases_amount
                )
            );
        }

        /**
         * We'll keep original products amounts and quantity
         * this means we're doing a full refund of price and quantities
         */
        $responseData[ 'data' ][ 'order' ][ 'products' ] = collect( $responseData[ 'data' ][ 'order' ][ 'products' ] )->map( function ( $product ) use ( $refundQuantity ) {
            $product[ 'condition' ] = OrderProductRefund::CONDITION_DAMAGED;
            $product[ 'quantity' ] = $refundQuantity;
            $product[ 'description' ] = __( 'Test : The product wasn\'t properly manufactured, causing external damage to the device during the shipment.' );

            return $product;
        })->toArray();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $responseData[ 'data' ][ 'order' ][ 'id' ] . '/refund', [
                'payment' => [
                    'identifier' => 'account-payment',
                ],
                'total' => $responseData[ 'data' ][ 'order' ][ 'total' ],
                'products' => $responseData[ 'data' ][ 'order' ][ 'products' ],
            ]);

        $response->assertStatus(200);
        $responseData = json_decode( $response->getContent(), true );

        /**
         * We need to check if the order
         * is correctly updated after a refund.
         */
        $order = Order::find( $responseData[ 'data' ][ 'order' ][ 'id' ] );

        $this->assertTrue( $order->payment_status === $paymentStatus, $message );

        $thirdFetchCustomer = $secondFetchCustomer->fresh();

        if (
            $currency->define( $thirdFetchCustomer->purchases_amount )
                ->additionateBy( $responseData[ 'data' ][ 'orderRefund' ][ 'total' ] )
                ->getRaw() !== $currency->getRaw( $secondFetchCustomer->purchases_amount ) ) {
            throw new Exception(
                sprintf(
                    __( 'The purchase amount hasn\'t been updated correctly. Expected %s, got %s' ),
                    $secondFetchCustomer->purchases_amount,
                    $thirdFetchCustomer->purchases_amount + (float) $responseData[ 'data' ][ 'orderRefund' ][ 'total' ]
                )
            );
        }

        /**
         * let's check if an expense has been created accordingly
         */
        // ns_sales_refunds_cashflow_account
        $expenseCategory = AccountType::find( ns()->option->get( 'ns_sales_refunds_account' ) );

        if ( ! $expenseCategory instanceof AccountType ) {
            throw new Exception( __( 'An expense hasn\'t been created after the refund.' ) );
        }

        $expenseValue = $expenseCategory->cashFlowHistories()
            ->where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
            ->sum( 'value' );

        if ( (float) $expenseValue != (float) $responseData[ 'data' ][ 'orderRefund' ][ 'total' ] ) {
            throw new Exception( __( 'The expense created after the refund doesn\'t match the order refund total.' ) );
        }

        $response->assertJson([
            'status' => 'success',
        ]);
    }

    public function attemptCreateOrderWithInstalment()
    {
        /**
         * @var CurrencyService
         */
        $currency = app()->make( CurrencyService::class );

        /**
         * @var OrdersService
         */
        $orderService = app()->make( OrdersService::class );
        $faker = Factory::create();
        $products = Product::whereRelation( 'unit_quantities', 'quantity', '>', 2000 )
            ->where( 'type', '!=', Product::TYPE_GROUPED )
            ->with([ 'unit_quantities' => fn( $query ) => $query->where( 'quantity', '>', 2000 ) ])
            ->get()
            ->shuffle()->take(1);
        $shippingFees = $faker->randomElement([100, 150, 200, 250, 300, 350, 400]);
        $discountRate = $faker->numberBetween(1, 5);

        $products = $products->map( function ( $product ) use ( $faker ) {
            $unitElement = $faker->randomElement( $product->unit_quantities );

            return [
                'product_id' => $product->id,
                'quantity' => $faker->numberBetween(1, 10), // 2,
                'unit_price' => $unitElement->sale_price, // 110.8402,
                'unit_quantity_id' => $unitElement->id,
            ];
        });

        /**
         * testing customer balance
         */
        $customer = Customer::first();

        $subtotal = ns()->currency->getRaw( $products->map( function ( $product ) {
            return Currency::raw( $product[ 'unit_price' ] ) * Currency::raw( $product[ 'quantity' ] );
        })->sum() );

        $initialTotalInstallment = 2;
        $discountValue = $orderService->computeDiscountValues( $discountRate, $subtotal );
        $total = ns()->currency->getRaw( ( $subtotal + $shippingFees ) - $discountValue );

        $paymentAmount = ns()->currency->getRaw( $total / 2 );

        $instalmentSlice = $total / 2;
        $instalmentPayment = ns()->currency->getRaw( $instalmentSlice );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id' => $customer->id,
                'type' => [ 'identifier' => 'takeaway' ],
                'discount_percentage' => $discountRate,
                'discount_type' => 'flat',
                'discount' => $discountValue,
                'addresses' => [
                    'shipping' => [
                        'name' => 'First Name Delivery',
                        'surname' => 'Surname',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'name' => 'EBENE Voundi',
                        'surname' => 'Antony Hervé',
                        'country' => 'United State Seattle',
                    ],
                ],
                'coupons' => [],
                'subtotal' => $subtotal,
                'shipping' => $shippingFees,
                'total' => $total,
                'tendered' => ns()->currency
                    ->getRaw( $total / 2 ),
                'total_instalments' => $initialTotalInstallment,
                'instalments' => [
                    [
                        'date' => ns()->date->getNowFormatted(),
                        'amount' => $instalmentPayment,
                    ], [
                        'date' => ns()->date->copy()->addDays(2)->toDateTimeString(),
                        'amount' => $instalmentPayment,
                    ],
                ],
                'products' => $products->toArray(),
                'payments' => [
                    [
                        'identifier' => 'cash-payment',
                        'value' => $paymentAmount,
                    ],
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        $responseData = json_decode( $response->getContent(), true );

        /**
         * Editing the instalment
         */
        $today = ns()->date->toDateTimeString();
        $order = $responseData[ 'data' ][ 'order' ];
        $instalment = OrderInstalment::where( 'order_id', $order[ 'id' ] )->where( 'paid', false )->get()->random();
        $instalmentAmount = ns()->currency->getRaw( $instalment->amount / 2 );
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments/' . $instalment->id, [
                'instalment' => [
                    'date' => $today,
                    'amount' => $instalmentAmount,
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        $instalment->refresh();

        if ( $instalment->date != $today ) {
            throw new Exception( __( 'The modification of the instalment has failed' ) );
        }

        /**
         * Add instalment
         */
        $order = Order::find( $order[ 'id' ] );
        $oldInstlaments = $order->total_instalments;
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments', [
                'instalment' => [
                    'date' => $today,
                    'amount' => $instalmentAmount,
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        $order->refresh();

        if ( $initialTotalInstallment === $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been registered.' ) );
        }

        if ( $oldInstlaments >= $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been registered.' ) );
        }

        $responseData = json_decode( $response->getContent(), true );

        /**
         * Delete Instalment
         */
        $order = Order::find( $order[ 'id' ] );
        $oldInstlaments = $order->total_instalments;
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'DELETE', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments/' . $responseData[ 'data' ][ 'instalment' ][ 'id' ] );

        $response->assertJson([
            'status' => 'success',
        ]);

        $order->refresh();

        if ( $oldInstlaments === $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been deleted.' ) );
        }

        /**
         * restore deleted instalment
         */
        $order = Order::find( $order[ 'id' ] );
        $oldInstlaments = $order->total_instalments;
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders/' . $order[ 'id' ] . '/instalments', [
                'instalment' => [
                    'date' => $today,
                    'amount' => $instalmentAmount,
                ],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        $order->refresh();

        if ( $oldInstlaments >= $order->total_instalments ) {
            throw new Exception( __( 'The instalment hasn\'t been registered.' ) );
        }

        $responseData = json_decode( $response->getContent(), true );

        /**
         * paying instalment
         */
        OrderInstalment::where( 'order_id', $order->id )
            ->where( 'paid', false )
            ->get()
            ->each( function ( $instalment ) use ( $order ) {
                $response = $this->withSession( $this->app[ 'session' ]->all() )
                    ->json( 'POST', 'api/nexopos/v4/orders/' . $order->id . '/instalments/' . $instalment->id . '/pay', [
                        'payment_type' => OrderPayment::PAYMENT_CASH,
                    ]);

                $response->assertJson([
                    'status' => 'success',
                ]);

                $instalment->refresh();

                $this->assertTrue( $instalment->paid, __( 'The instalment hasn\'t been paid.' ) );
            });
    }

    protected function attemptCreatePartiallyPaidOrderWithAdjustment()
    {
        $currency = app()->make( CurrencyService::class );

        $customer = Customer::first();
        $customer->credit_limit_amount = 0;
        $customer->save();

        $product = Product::withStockEnabled()
            ->whereRelation( 'unit_quantities', 'quantity', '>', 500 )
            ->with( 'unit_quantities', function ( $query ) {
                $query->where( 'quantity', '>', 500 );
            })
            ->first();
        $shippingFees = 150;
        $discountRate = 3.5;
        $products = [
            [
                'product_id' => $product->id,
                'quantity' => 5,
                'unit_price' => $product->unit_quantities[0]->sale_price,
                'unit_quantity_id' => $product->unit_quantities[0]->id,
            ],
        ];

        $subtotal = collect( $products )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', [
                'customer_id' => $customer->id,
                'type' => [ 'identifier' => 'takeaway' ],
                'discount_type' => 'percentage',
                'discount_percentage' => $discountRate,
                'addresses' => [
                    'shipping' => [
                        'name' => 'First Name Delivery',
                        'surname' => 'Surname',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'name' => 'EBENE Voundi',
                        'surname' => 'Antony Hervé',
                        'country' => 'United State Seattle',
                    ],
                ],
                'subtotal' => $subtotal,
                'shipping' => $shippingFees,
                'products' => $products,
                'payments' => [],
                'payment_status' => Order::PAYMENT_UNPAID,
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        $responseData = json_decode( $response->getContent(), true );

        /**
         * Test 1: Testing Product History
         * We should test if the records
         * for partially paid order was created
         */
        foreach ( $products as $product ) {
            $history = ProductHistory::where( 'product_id', $product[ 'product_id' ] )
                ->where( 'operation_type', ProductHistory::ACTION_SOLD )
                ->where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
                ->first();

            $this->assertTrue( $history instanceof ProductHistory, 'No product history was created after placing the order with partial payment.' );
            $this->assertTrue( (float) $history->quantity === (float) $product[ 'quantity' ], 'The quantity of the product doesn\'t match the product history quantity.' );
        }

        /**
         * Step 2: We'll here increase the
         * quantity of the product attached to the order.
         */
        $newProducts = $responseData[ 'data' ][ 'order' ][ 'products' ];
        Arr::set( $newProducts, '0.quantity', $newProducts[0][ 'quantity' ] + 1 );

        $shippingFees = 150;
        $discountRate = 3.5;

        $subtotal = collect( $responseData[ 'data' ][ 'order' ][ 'products' ] )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $responseData[ 'data' ][ 'order' ][ 'id' ], [
                'customer_id' => 1,
                'type' => [ 'identifier' => 'takeaway' ],
                'discount_type' => 'percentage',
                'discount_percentage' => $discountRate,
                'addresses' => [
                    'shipping' => [
                        'name' => 'First Name Delivery',
                        'surname' => 'Surname',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'name' => 'EBENE Voundi',
                        'surname' => 'Antony Hervé',
                        'country' => 'United State Seattle',
                    ],
                ],
                'subtotal' => $subtotal,
                'shipping' => $shippingFees,
                'products' => $newProducts,
                'payments' => [],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        $response->assertStatus(200);
        $json = $response->json();

        /**
         * Test 2: Testing Product History
         * We should test if the records
         * for partially paid order was created
         */
        foreach ( $products as $product ) {
            $historyActionAdjustmentSale = ProductHistory::where( 'product_id', $product[ 'product_id' ] )
                ->where( 'operation_type', ProductHistory::ACTION_ADJUSTMENT_SALE )
                ->where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
                ->first();

            $this->assertTrue( $historyActionAdjustmentSale instanceof ProductHistory, 'The created history doesn\'t match what should have been created after an order modification.' );
            $this->assertSame(
                (float) $historyActionAdjustmentSale->quantity,
                (float) $json[ 'data' ][ 'order' ][ 'products' ][0][ 'quantity' ] - (float) $responseData[ 'data' ][ 'order' ][ 'products' ][0][ 'quantity' ],
                'The quantity of the product doesn\'t match the new product quantity after the order modfiication.'
            );
        }

        /**
         * Step 3: We'll here decrease the
         * quantity of the product attached to the order.
         */
        $newProducts = $responseData[ 'data' ][ 'order' ][ 'products' ];
        Arr::set( $newProducts, '0.quantity', $newProducts[0][ 'quantity' ] - 2 );

        $shippingFees = 150;
        $discountRate = 3.5;

        $subtotal = collect( $responseData[ 'data' ][ 'order' ][ 'products' ] )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'PUT', 'api/nexopos/v4/orders/' . $responseData[ 'data' ][ 'order' ][ 'id' ], [
                'customer_id' => 1,
                'type' => [ 'identifier' => 'takeaway' ],
                'discount_type' => 'percentage',
                'discount_percentage' => $discountRate,
                'addresses' => [
                    'shipping' => [
                        'name' => 'First Name Delivery',
                        'surname' => 'Surname',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'name' => 'EBENE Voundi',
                        'surname' => 'Antony Hervé',
                        'country' => 'United State Seattle',
                    ],
                ],
                'subtotal' => $subtotal,
                'shipping' => $shippingFees,
                'products' => $newProducts,
                'payments' => [],
            ]);

        $response->assertJson([
            'status' => 'success',
        ]);

        $response->assertStatus(200);
        $json2 = $response->json();

        /**
         * Test 3: Testing Product History
         * We should test if the records
         * for partially paid order was created
         */
        foreach ( $products as $product ) {
            $historyActionAdjustmentSale = ProductHistory::where( 'product_id', $product[ 'product_id' ] )
                ->where( 'operation_type', ProductHistory::ACTION_ADJUSTMENT_RETURN )
                ->where( 'order_id', $responseData[ 'data' ][ 'order' ][ 'id' ] )
                ->first();

            $this->assertTrue( $historyActionAdjustmentSale instanceof ProductHistory, 'The created history doesn\'t match what should have been created after an order modification.' );
            $this->assertSame(
                (float) $historyActionAdjustmentSale->quantity,
                (float) $json[ 'data' ][ 'order' ][ 'products' ][0][ 'quantity' ] - (float) $json2[ 'data' ][ 'order' ][ 'products' ][0][ 'quantity' ],
                'The quantity of the product doesn\'t match the new product quantity after the order modfiication.'
            );
        }
    }

    protected function attemptTestRewardSystem()
    {
        $reward = RewardSystem::with( 'rules' )->first();
        $rules = $reward->rules->sortBy( 'reward' )->reverse();
        $timesForOrders = ( $reward->target / $rules->first()->reward );

        $product = Product::withStockEnabled()
            ->where( 'type', '!=', Product::TYPE_GROUPED )
            ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
            ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
            ->get()
            ->random();

        $unit = $product->unit_quantities()->where( 'quantity', '>', 0 )->first();
        $product_price = $this->faker->numberBetween( $rules->first()->from, $rules->first()->to );
        $subtotal = $product_price;
        $shippingFees = 0;

        /**
         * We'll set a fixed quantity to avoid failling
         * on not enough stock error
         */
        $unit->quantity = 10000;
        $unit->save();

        $customer = Customer::first();

        if ( ! $customer->group->reward instanceof RewardSystem ) {
            $customer->group->reward_system_id = $reward->id;
            $customer->group->save();
        }

        $previousCoupons = $customer->coupons()->count();

        for ( $i = 0; $i < $timesForOrders; $i++ ) {
            $response = $this->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', 'api/nexopos/v4/orders', [
                    'customer_id' => $customer->id,
                    'type' => [ 'identifier' => 'takeaway' ],
                    // 'discount_type'         =>  'percentage',
                    // 'discount_percentage'   =>  2.5,
                    'addresses' => [
                        'shipping' => [
                            'name' => 'First Name Delivery',
                            'surname' => 'Surname',
                            'country' => 'Cameroon',
                        ],
                        'billing' => [
                            'name' => 'EBENE Voundi',
                            'surname' => 'Antony Hervé',
                            'country' => 'United State Seattle',
                        ],
                    ],
                    'subtotal' => $subtotal,
                    'shipping' => $shippingFees,
                    'products' => [
                        [
                            'product_id' => $product->id,
                            'quantity' => 1,
                            'unit_price' => $product_price,
                            'unit_quantity_id' => $unit->id,
                            'custom' => 'retail',
                        ],
                    ],
                    'payments' => [
                        [
                            'identifier' => 'paypal-payment',
                            'value' => ns()->currency->define( $subtotal )
                                ->additionateBy( $shippingFees )
                                ->getRaw(),
                        ],
                    ],
                ]);

            $response->assertStatus( 200 );
        }

        $currentCoupons = $customer->coupons()->count();

        $response->assertJsonPath( 'data.order.payment_status', Order::PAYMENT_PAID );
        $this->assertTrue( $previousCoupons < $currentCoupons, __( 'The coupons count has\'nt changed.' ) );
    }

    protected function attemptCouponUsage()
    {
        /**
         * We'll try to see if a coupon
         * has been issued by the end of this reward
         */
        $faker = Factory::create();
        $customerCoupon = CustomerCoupon::where( 'customer_id', '!=', 0 )->get()->last();

        $customer = $customerCoupon->customer;
        $products = $this->retreiveProducts();
        $shippingFees = 0;
        $subtotal = $products->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();

        if ( $customerCoupon instanceof CustomerCoupon ) {
            $allCoupons = [
                [
                    'customer_coupon_id' => $customerCoupon->id,
                    'coupon_id' => $customerCoupon->coupon_id,
                    'name' => $customerCoupon->name,
                    'type' => 'percentage_discount',
                    'code' => $customerCoupon->code,
                    'limit_usage' => $customerCoupon->coupon->limit_usage,
                    'value' => ns()->currency->define( $customerCoupon->coupon->discount_value )
                        ->multiplyBy( $subtotal )
                        ->divideBy( 100 )
                        ->getRaw(),
                    'discount_value' => $customerCoupon->coupon->discount_value,
                    'minimum_cart_value' => $customerCoupon->coupon->minimum_cart_value,
                    'maximum_cart_value' => $customerCoupon->coupon->maximum_cart_value,
                ],
            ];

            $totalCoupons = collect( $allCoupons )->map( fn( $coupon ) => $coupon[ 'value' ] )->sum();
        } else {
            $allCoupons = [];
            $totalCoupons = 0;
        }

        $discount = [
            'type' => $faker->randomElement([ 'percentage', 'flat' ]),
        ];

        $dateString = ns()->date->startOfDay()->addHours(
            $faker->numberBetween( 0, 23 )
        )->format( 'Y-m-d H:m:s' );

        $orderData = [
            'customer_id' => $customer->id,
            'type' => [ 'identifier' => 'takeaway' ],
            'discount_type' => $discount[ 'type' ],
            'discount_percentage' => $discount[ 'rate' ] ?? 0,
            'discount' => $discount[ 'value' ] ?? 0,
            'addresses' => [
                'shipping' => [
                    'name' => 'First Name Delivery',
                    'surname' => 'Surname',
                    'country' => 'Cameroon',
                ],
                'billing' => [
                    'name' => 'EBENE Voundi',
                    'surname' => 'Antony Hervé',
                    'country' => 'United State Seattle',
                ],
            ],
            'author' => ! empty( $this->users ) // we want to randomise the users
                ? collect( $this->users )->suffle()->first()
                : User::get( 'id' )->pluck( 'id' )->shuffle()->first(),
            'coupons' => $allCoupons,
            'subtotal' => $subtotal,
            'shipping' => $shippingFees,
            'products' => $products->toArray(),
            'payments' => [
                [
                    'identifier' => 'cash-payment',
                    'value' => ns()->currency->define( ( $subtotal + $shippingFees ) - $totalCoupons )
                        ->getRaw(),
                ],
            ],
        ];

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/nexopos/v4/orders', $orderData );

        $response->assertJson([
            'status' => 'success',
        ]);

        /**
         * check if coupon usage has been updated.
         */
        $oldUsage = $customerCoupon->usage;
        $customerCoupon->refresh();

        $this->assertTrue( $oldUsage !== $customerCoupon->usage, __( 'The coupon usage hasn\'t been updated.' ) );
    }

    private function retreiveProducts()
    {
        $products = Product::with([ 'unit_quantities' => fn( $query ) => $query->where( 'quantity', '>', 1000 ) ])
            ->whereRelation( 'unit_quantities', 'quantity', '>', 1000 )
            ->get()
            ->shuffle()
            ->take(3);

        return $products->map( function ( $product ) {
            $unitElement = $this->faker->randomElement( $product->unit_quantities );

            $data = [
                'name' => 'Fees',
                'quantity' => $this->faker->numberBetween(1, 10),
                'unit_price' => $unitElement->sale_price,
                'tax_type' => 'inclusive',
                'tax_group_id' => 1,
                'unit_id' => $unitElement->unit_id,
            ];

            if ( $this->faker->randomElement([ false, true ]) ) {
                $data[ 'product_id' ] = $product->id;
                $data[ 'unit_quantity_id' ] = $unitElement->id;
            }

            return $data;
        })->filter( function ( $product ) {
            return $product[ 'quantity' ] > 0;
        });
    }
}
