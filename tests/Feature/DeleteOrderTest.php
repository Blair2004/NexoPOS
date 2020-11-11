<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteOrderTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testDeleteOrder()
    {
        Sanctum::actingAs(
            User::find(98),
            ['*']
        );

        $order          =   Order::latest()->limit(1)->get()->first();

        if ( $order instanceof Order ) {
            $response = $this
                ->withSession( $this->app[ 'session' ]->all() )
                ->json( 'DELETE', '/api/nexopos/v4/orders/' . $order->id );

            return $response->assertJsonPath( 'status', 'success' );
        }
        
        throw new Exception( __( 'Not able to find an order to perform the tests' ) );
    }
}
