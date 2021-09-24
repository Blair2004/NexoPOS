<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerCoupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\RewardSystem;
use App\Models\Role;
use App\Models\User;
use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TestRewardSystem extends TestCase
{
    use WithFaker;
    
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_reward_system()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

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

    public function test_coupon_usage()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

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
