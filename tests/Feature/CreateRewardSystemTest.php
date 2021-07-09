<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Role;
use Carbon\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateRewardSystemTest extends TestCase
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
            ->json( 'post', 'api/nexopos/v4/crud/ns.rewards-system', [
                'name'          =>  __( 'Sample Reward System' ),
                'general'       =>  [
                    'coupon_id'         =>  $this->faker->randomElement( Coupon::get()->map( fn( $coupon ) => $coupon->id )->toArray() ),
                    'target'            =>  $this->faker->randomElement([ 10, 20, 30 ]),
                ],
                'rules'         =>  [
                    [
                        'from'      =>  0,
                        'to'        =>  10,
                        'reward'    =>  1
                    ], [
                        'from'      =>  10,
                        'to'        =>  50,
                        'reward'    =>  3
                    ], [
                        'from'      =>  50,
                        'to'        =>  100,
                        'reward'    =>  5
                    ]
                ]
            ]);

        $response->assertStatus(200);
    }
}
