<?php

namespace Tests\Feature;

use App\Classes\Currency;
use App\Models\CashFlow;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Services\CurrencyService;
use App\Services\OrdersService;
use App\Services\ProductService;
use Exception;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class CreateOrderPaidWithCustomerCredit extends TestCase
{
    use WithAuthentication, WithOrderTest;

    protected $count                =   1;
    protected $totalDaysInterval    =   1;
    protected $shouldMakePayment    =   true;
    
    public function test_make_order_on_credit()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        /**
         * @var CurrencyService
         */
        $currency       =   app()->make( CurrencyService::class );
        $faker          =   Factory::create();

        $customer                       =   Customer::first();
        $customer->credit_limit_amount  =   5;
        $customer->save();

        $products           =   Product::with( 'unit_quantities' )->get()->shuffle()->take(3);
        $products           =   $products->map( function( $product ) use ( $faker ) {
            $unitElement    =   $faker->randomElement( $product->unit_quantities );

            $data           =   array_merge([
                'name'                  =>  $product->name,
                'quantity'              =>  $faker->numberBetween(1,10),
                'unit_price'            =>  $unitElement->sale_price,
                'tax_type'              =>  'inclusive',
                'tax_group_id'          =>  1,
                'unit_id'               =>  $unitElement->unit_id,
            ], $this->customProductParams );

            if ( $faker->randomElement([ true ]) ) {
                $data[ 'product_id' ]       =   $product->id;
                $data[ 'unit_quantity_id' ] =   $unitElement->id;
            }

            return $data;
        })->filter( function( $product ) {
            return $product[ 'quantity' ] > 0;
        });

        $shippingFees   =   $faker->randomElement([10,15,20,25,30,35,40]);
        $subtotal   =   ns()->currency->getRaw( $products->map( function( $product ) use ($currency) {
            return $currency
                ->define( $product[ 'unit_price' ] )
                ->multiplyBy( $product[ 'quantity' ] )
                ->getRaw();
        })->sum() );

        $this->customOrderParams    =   [
            'customer_id'           =>  $customer->id,
            'products'              =>  $products->toArray(),
            'payment_status'        =>  Order::PAYMENT_UNPAID,
            'payments'              =>  [] // no payment
        ];

        /**
         * first attempt to post an order
         * over a table.
         */
        $this->defaultProcessing    =   true;
        
        $response   =   $this->attemptPostOrder( function( $response, $data ) {
            $order  =   Order::find( $data[ 'data' ][ 'order' ][ 'id' ] );
        });

        $this->defaultProcessing    =   false;
    }

    public function processOrders( $currentDate, $callback )
    {
        $responses      =   [];
        /**
         * @var CurrencyService
         */
        $currency       =   app()->make( CurrencyService::class );
        $faker          =   Factory::create();

        for( $i = 0; $i < $this->count; $i++ ) {

            $singleResponse     =   [];
            
            $products       =   Product::with( 'unit_quantities' )->get()->shuffle()->take(3);
            $shippingFees   =   $faker->randomElement([10,15,20,25,30,35,40]);
            $discountRate   =   $faker->numberBetween(0,5);

            $products           =   $products->map( function( $product ) use ( $faker ) {
                $unitElement    =   $faker->randomElement( $product->unit_quantities );

                $data           =   array_merge([
                    'name'                  =>  'Fees',
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

            $subtotal   =   ns()->currency->getRaw( $products->map( function( $product ) use ($currency) {
                return $currency
                    ->define( $product[ 'unit_price' ] )
                    ->multiplyBy( $product[ 'quantity' ] )
                    ->getRaw();
            })->sum() );

            
            $allCoupons             =   [];
            $totalCoupons           =   0;

            $discount           =   [
                'type'      =>  $faker->randomElement([ 'percentage', 'flat' ]),
                'value'     =>  0,
                'rate'      =>  0,
            ];
                        
            $discountCoupons    =   $currency->define( $discount[ 'value' ] )
                ->additionateBy( $allCoupons[0][ 'value' ] ?? 0 )
                ->getRaw();

            $dateString         =   $currentDate->startOfDay()->addHours( 
                $faker->numberBetween( 0,23 ) 
            )->format( 'Y-m-d H:m:s' );

            $customer                   =   Customer::get()->random();

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
                    
            $response->assertStatus(401);
            $response->assertJsonPath( 'message', 'By proceeding this order, the customer will exceed the maximum credit allowed for his account: USD 5 .' );
        }

        return $responses;
    }
}
