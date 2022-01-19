<?php
namespace Tests\Traits;

use App\Models\Product;
use Illuminate\Foundation\Testing\WithFaker;

trait WithCouponTest
{
    use WithFaker;
    
    protected function attemptCreatecoupon()
    {
        $response       =   $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'post', 'api/nexopos/v4/crud/ns.coupons', [
                'name'          =>  $this->faker->name,
                'general'       =>  [
                    'type'              =>  'percentage_discount',
                    'code'              =>  'cp-' . $this->faker->numberBetween(0,9) . $this->faker->numberBetween(0,9),
                    'discount_value'    =>  $this->faker->randomElement([ 10, 15, 20, 25 ]),
                    'limit_usage'       =>  $this->faker->randomElement([ 100, 200, 400 ]),        
                ],
                'selected_products'     =>  [
                    'products'          =>  Product::select( 'id' )
                        ->get()
                        ->map( fn( $product ) => $product->id )
                        ->toArray()
                ],
                'selected_categories'   =>  [
                    'categories'        =>    Product::select( 'id' )
                        ->get()
                        ->map( fn( $product ) => $product->id )
                        ->toArray()
                ]
            ]);

        $response->assertJsonPath( 'status', 'success' );
    }
}