<?php

namespace Tests\Feature;

use App\Models\Role;
use Carbon\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateCouponTest extends TestCase
{
    use WithFaker;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testExample()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', 'api/nexopos/v4/crud/ns.coupons', [
                'name'          =>  __( 'Sample Coupon' ),
                'general'       =>  [
                    'type'              =>  'percentage_discount',
                    'code'              =>  $this->faker->name,
                    'discount_value'    =>  $this->faker->randomElement([ 10, 15, 20, 25 ]),
                    'limit_usage'       =>  $this->faker->randomElement([ 1, 5, 10 ]),        
                ],
                'selected_products'     =>  [
                    'products'      =>  [],
                ],
                'selected_categories'     =>  [
                    'categories'      =>  [],
                ]
            ]);

        $response->assertJsonPath( 'status', 'success' );
    }
}
