<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\RewardSystem;
use App\Models\Role;
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
        $subtotal           =   $unit->sale_price * 5;
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
                            'unit_price'            =>  $this->faker->numberBetween( $rules->first()->from, $rules->first()->to ),
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
        
        $this->assertTrue( $previousCoupons < $currentCoupons, __( 'The coupons count has\'nt changed.' ) );
    }
}
