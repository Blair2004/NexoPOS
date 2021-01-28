<?php

namespace Tests\Feature;

use App\Models\RewardSystem;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateCustomerGroupTest extends TestCase
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
            ->json( 'POST', 'api/nexopos/v4/crud/ns.customers-groups', [
                'name'  =>  __( 'Base Customers' ),
                'general'   =>  [
                    'reward_system_id'  =>  $this->faker->randomElement( RewardSystem::get()->map( fn( $reward ) => $reward->id )->toArray() )
                ]
            ]);

        $response->assertJson([
            'status'    =>  'success'
        ]);
    }
}
