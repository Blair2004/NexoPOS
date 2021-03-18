<?php

namespace Tests\Feature;

use App\Models\Role;
use Carbon\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\Product;
use App\Models\ProductCategory;

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
                'name'          =>  $this->faker->name,
                'general'       =>  [
                    'type'              =>  'percentage_discount',
                    'code'              =>  'cp-' . $this->faker->numberBetween(0,9) . $this->faker->numberBetween(0,9),
                    'discount_value'    =>  $this->faker->randomElement([ 10, 15, 20, 25 ]),
                    'limit_usage'       =>  $this->faker->randomElement([ 30, 40, 50 ]),        
                ],
                'selected_products'     =>  [
                    'products'      =>  Product::select( 'id' )
                        ->get()
                        ->map( fn( $product ) => $product->id )
                        ->toArray()
                ],
                'selected_categories'     =>  [
                    'categories'      =>  ProductCategory::select( 'id' )
                        ->get()
                        ->map( fn( $product ) => $product->id )
                        ->toArray(),
                ]
            ]);

        $response->assertJsonPath( 'status', 'success' );
    }
}
