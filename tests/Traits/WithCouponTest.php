<?php

namespace Tests\Traits;

use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductCategory;
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

    protected function attemptAssignCouponToOrder()
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
        
        $order              =   [
            'created_at'    =>  ns()->date->now()->toDateTimeString(),
            'shipping'      =>  30,
            'products'  =>  $products,
            'coupons'   =>  [
                [
                    'id'                    =>  $couponResponse[ 'data' ][ 'entry' ][ 'id' ],
                    'minimum_cart_value'    =>  $couponResponse[ 'data' ][ 'entry' ][ 'minimum_cart_value' ],
                    'maximum_cart_value'    =>  $couponResponse[ 'data' ][ 'entry' ][ 'maximum_cart_value' ],
                    'discount_value'        =>  $couponResponse[ 'data' ][ 'entry' ][ 'discount_value' ],
                    'name'                  =>  $couponResponse[ 'data' ][ 'entry' ][ 'name' ],
                    'discount_type'         =>  $couponResponse[ 'data' ][ 'entry' ][ 'type' ],
                    'limit_usage'           =>  $couponResponse[ 'data' ][ 'entry' ][ 'limit_usage' ],
                    'value'                 =>  $couponValue
                ]
            ]
        ];

        $order   =   json_decode( file_get_contents( 'order.json' ), true );

        $result     =   $this->processOrders( $order ); 

        return $result;
    }
}
