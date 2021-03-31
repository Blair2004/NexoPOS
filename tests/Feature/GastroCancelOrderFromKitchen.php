<?php

namespace Tests\Feature;

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Modules\NsGastro\Models\Order;
use Modules\NsGastro\Services\KitchenService;
use Tests\TestCase;

class GastroCancelOrderFromKitchen extends CreateOrderTest
{
    protected $count    =   1;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCookingOrder()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );
        
        $this->testPostingOrder();

        /**
         * @var KitchenService
         */
        $kitchenService     =   app()->make( KitchenService::class );

        /**
         * @var Order
         */
        $order              =   Order::orderBy( 'id', 'desc' )->first();
        
        $order->products->each( function( $product ) use ( $kitchenService, $order ) {
            $kitchen    =   $kitchenService->getKitchenFromCategory( $product->product_category_id );

            /**
             * this will send the cook request for the meal
             */
            $response   =   $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'POST', '/api/nexopos/v4/gastro/kitchens/' . $kitchen->id . '/cook/' . $order->id, [
                    'products'  =>  [ $product->id ]
                ]);

            $response->assertJsonPath( 'status', 'success' );
        });
    }
}
