<?php

namespace Tests\Traits;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\CustomerCoupon;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Services\CustomerService;
use App\Services\TaxService;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\TestResponse;
trait WithCouponTest
{
    use WithFaker, WithOrderTest, WithProductTest;

    protected function attemptCreatecoupon()
    {
        /**
         * @var TestResponse
         */
        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', 'api/crud/ns.coupons', [
                'name' => $this->faker->name,
                'general' => [
                    'type' => 'percentage_discount',
                    'code' => 'cp-' . $this->faker->numberBetween(0, 9) . $this->faker->numberBetween(0, 9),
                    'discount_value' => $this->faker->randomElement([ 10, 15, 20, 25 ]),
                    'limit_usage' => $this->faker->randomElement([ 100, 200, 400 ]),
                ],
                'selected_products' => [
                    'products' => Product::select( 'id' )
                        ->get()
                        ->map( fn( $product ) => $product->id )
                        ->toArray(),
                ],
                'selected_categories' => [
                    'categories' => ProductCategory::select( 'id' )
                        ->get()
                        ->map( fn( $product ) => $product->id )
                        ->toArray(),
                ],
            ]);

        $response->assertJsonPath( 'status', 'success' );

        return $response;
    }

    protected function attemptUpdateCoupon()
    {
        /**
         * just in case the function executes before
         * the coupon creation.
         */
        $this->attemptCreatecoupon();

        $coupon = Coupon::first();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'put', 'api/crud/ns.coupons/' . $coupon->id, [
                'name' => $this->faker->name,
                'general' => [
                    'type' => 'percentage_discount',
                    'code' => 'cp-' . $this->faker->numberBetween(0, 9) . $this->faker->numberBetween(0, 9),
                    'discount_value' => $this->faker->randomElement([ 10, 15, 20, 25 ]),
                    'limit_usage' => $this->faker->randomElement([ 100, 200, 400 ]),
                ],
                'selected_products' => [
                    'products' => Product::select( 'id' )
                        ->get()
                        ->map( fn( $product ) => $product->id )
                        ->toArray(),
                ],
                'selected_categories' => [
                    'categories' => ProductCategory::select( 'id' )
                        ->get()
                        ->map( fn( $product ) => $product->id )
                        ->toArray(),
                ],
            ]);

        $response->assertJsonPath( 'status', 'success' );
    }

    protected function attemptAssigningANonExistingCoupon()
    {
        $customer    =   Customer::get( 'id' )->random();

        $products   =   [
            $this->orderProduct(
                name: 'Test Product',
                unit_price: 100,
                quantity: 2
            )
        ];

        $order              =   [
            'created_at'    =>  ns()->date->now()->toDateTimeString(),
            'shipping'      =>  30,
            'customer_id'   =>  $customer->id,
            'products'  =>  $products,
            'coupons'   =>  [
                [
                    'id'                    =>  999999999999999999999999999999,
                    'minimum_cart_value'    =>  0,
                    'maximum_cart_value'    =>  0,
                    'discount_value'        =>  0,
                    'name'                  =>  'Unammed',
                    'type'                  =>  'percentage',
                    'limit_usage'           =>  0,
                    'value'                 =>  0,
                ]
            ]
        ];

        $this->shouldMakePayment    =   false;
        $this->shouldRefund         =   false;

        $this->processOrders( $order, function( $response ) {
            $response->assertJson([
                'status'    =>  'failed'
            ]);
        }); 
    }

    public function attemptUseExaustedCoupon()
    {
        $customer    =   Customer::get( 'id' )->random();

        /**
         * @var TaxService $taxService 
         */
        $taxService     =   app()->make( TaxService::class );

        /**
         * @var CustomerService $customerService 
         */
        $customerService     =   app()->make( CustomerService::class );

        $couponResponse   =   $this->attemptCreatecoupon()->json();
        $coupon     =   Coupon::find( $couponResponse[ 'data' ][ 'entry' ][ 'id' ] );

        // We only want this to be used once.
        $coupon->limit_usage    =   1;
        $coupon->save();

        $customerCoupon     =   $customerService->assignCouponUsage(
            customer_id: $customer->id,
            coupon: $coupon
        );

        // we'll fake a usage. The next usage
        // should trigger an error as the coupon usage is now exhausted
        $customerCoupon->usage  =   1;
        $customerCoupon->save();

        $this->assertTrue( $customerCoupon->coupon_id === $coupon->id, 'The coupon ID doesn\'t matches' );
        $this->assertTrue( $customer->coupons()->where( 'coupon_id', $coupon->id )->first() instanceof CustomerCoupon, 'The customer doesn\'t have any coupon assigned.' );

        $products   =   [
            $this->orderProduct(
                name: 'Test Product',
                unit_price: 100,
                quantity: 2
            )
        ];
        
        $subTotal       =   collect( $products )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();
        $couponValue    =   0;

        if ( $coupon instanceof Coupon ) {
            $couponValue    =   match( $coupon->type ) {
                Coupon::TYPE_PERCENTAGE => $taxService->getPercentageOf( $subTotal, $coupon->discount_value ),
                Coupon::TYPE_FLAT => $coupon->discount_value
            };
        }
        
        $order              =   [
            'created_at'    =>  ns()->date->now()->toDateTimeString(),
            'shipping'      =>  30,
            'customer_id'   =>  $customer->id,
            'products'  =>  $products,
            'coupons'   =>  [
                [
                    'id'                    =>  $coupon->id,
                    'minimum_cart_value'    =>  $coupon->minimum_cart_value,
                    'maximum_cart_value'    =>  $coupon->maximum_cart_value,
                    'discount_value'        =>  $coupon->discount_value,
                    'name'                  =>  $coupon->name,
                    'type'                  =>  $coupon->type,
                    'limit_usage'           =>  $coupon->limit_usage,
                    'value'                 =>  $couponValue
                ]
            ]
        ];

        /**
         * the order shouldn't be placed
         * as the coupon usage is exhausted
         */
        $this->processOrders( $order, function( $response ) {
            $response->assertJson([
                'status'    =>  'failed'
            ]);
        }); 
    }

    public function attemptUseCouponTillUsageIsExhausted()
    {
        $customer    =   Customer::get( 'id' )->random();

        /**
         * @var TaxService $taxService 
         */
        $taxService     =   app()->make( TaxService::class );

        /**
         * @var CustomerService $customerService 
         */
        $customerService     =   app()->make( CustomerService::class );

        $couponResponse   =   $this->attemptCreatecoupon()->json();
        $coupon     =   Coupon::find( $couponResponse[ 'data' ][ 'entry' ][ 'id' ] );

        // We only want this to be used once.
        $coupon->limit_usage    =   2;
        $coupon->save();

        $customerCoupon     =   $customerService->assignCouponUsage(
            customer_id: $customer->id,
            coupon: $coupon
        );

        $this->assertTrue( $customerCoupon->coupon_id === $coupon->id, 'The coupon ID doesn\'t matches' );
        $this->assertTrue( $customer->coupons()->where( 'coupon_id', $coupon->id )->first() instanceof CustomerCoupon, 'The customer doesn\'t have any coupon assigned.' );

        $products   =   [
            $this->orderProduct(
                name: 'Test Product',
                unit_price: 100,
                quantity: 2
            )
        ];
        
        $subTotal       =   collect( $products )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();
        $couponValue    =   0;

        if ( $coupon instanceof Coupon ) {
            $couponValue    =   match( $coupon->type ) {
                Coupon::TYPE_PERCENTAGE => $taxService->getPercentageOf( $subTotal, $coupon->discount_value ),
                Coupon::TYPE_FLAT => $coupon->discount_value
            };
        }
        
        $order              =   [
            'created_at'    =>  ns()->date->now()->toDateTimeString(),
            'shipping'      =>  30,
            'customer_id'   =>  $customer->id,
            'products'  =>  $products,
            'coupons'   =>  [
                [
                    'id'                    =>  $coupon->id,
                    'minimum_cart_value'    =>  $coupon->minimum_cart_value,
                    'maximum_cart_value'    =>  $coupon->maximum_cart_value,
                    'discount_value'        =>  $coupon->discount_value,
                    'name'                  =>  $coupon->name,
                    'type'                  =>  $coupon->type,
                    'limit_usage'           =>  $coupon->limit_usage,
                    'value'                 =>  $couponValue
                ]
            ]
        ];

        /**
         * the order shouldn't be placed
         * as the coupon usage is exhausted
         */
        $this->processOrders( $order, function( $response ) {
            $response->assertJson([
                'status'    =>  'success'
            ]);
        }); 

        $customerCoupon->refresh();

        $this->assertTrue( $customerCoupon->usage === 1, 'The coupon usage hasn\'t increased after a use.' );
        
        $this->processOrders( $order, function( $response ) {
            $response->assertJson([
                'status'    =>  'success'
            ]);
        }); 

        $customerCoupon->refresh();

        $this->assertTrue( $customerCoupon->usage === 2, 'The coupon usage hasn\'t increased after a second use.' );

        // be cause we only had 2 possible usage for that coupon.
        $this->processOrders( $order, function( $response ) {
            $response->assertJson([
                'status'    =>  'failed'
            ]);
        }); 
    }

    public function attemptAssignCouponToOrder()
    {
        /**
         * @var TaxService $taxService 
         */
        $taxService     =   app()->make( TaxService::class );
        $couponResponse   =   $this->attemptCreatecoupon()->json();
        $coupon     =   Coupon::find( $couponResponse[ 'data' ][ 'entry' ][ 'id' ] );
        $products   =   [
            $this->orderProduct(
                name: 'Test Product',
                unit_price: 100,
                quantity: 2
            )
        ];
        
        $subTotal       =   collect( $products )->map( fn( $product ) => $product[ 'unit_price' ] * $product[ 'quantity' ] )->sum();
        $couponValue    =   0;

        if ( $coupon instanceof Coupon ) {
            $couponValue    =   match( $coupon->type ) {
                Coupon::TYPE_PERCENTAGE => $taxService->getPercentageOf( $subTotal, $coupon->discount_value ),
                Coupon::TYPE_FLAT => $coupon->discount_value
            };
        }

        $customer    =   Customer::get( 'id' )->random();
        
        /**
         * First of all, we'll delete all generated coupon.
         * to ensure we can verify them easilly later.
         */
        $customer->coupons()->delete();
        
        $order              =   [
            'created_at'    =>  ns()->date->now()->toDateTimeString(),
            'shipping'      =>  30,
            'customer_id'   =>  $customer->id,
            'products'  =>  $products,
            'coupons'   =>  [
                [
                    'id'                    =>  $couponResponse[ 'data' ][ 'entry' ][ 'id' ],
                    'minimum_cart_value'    =>  $couponResponse[ 'data' ][ 'entry' ][ 'minimum_cart_value' ],
                    'maximum_cart_value'    =>  $couponResponse[ 'data' ][ 'entry' ][ 'maximum_cart_value' ],
                    'discount_value'        =>  $couponResponse[ 'data' ][ 'entry' ][ 'discount_value' ],
                    'name'                  =>  $couponResponse[ 'data' ][ 'entry' ][ 'name' ],
                    'type'                  =>  $couponResponse[ 'data' ][ 'entry' ][ 'type' ],
                    'limit_usage'           =>  $couponResponse[ 'data' ][ 'entry' ][ 'limit_usage' ],
                    'value'                 =>  $couponValue
                ]
            ]
        ];

        $result     =   $this->processOrders( $order ); 

        /**
         * We'll now try to figure out if there is a coupon generated
         * for the customer we've selected.
         */
        $this->assertTrue( $customer->coupons()->count() === 1, 'No coupon was created while using the coupon on the customer sale.' );
        $this->assertTrue( ( int ) $customer->coupons()->first()->coupon_id === ( int ) $coupon->id, 'The customer generated coupon doesn\'t match the coupon created earlier.' );

        return $result;
    }
}
