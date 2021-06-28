<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Role;
use App\Services\OrdersService;
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
    public function test_example()
    {
        Sanctum::actingAs(
            Role::namespace( 'admin' )->users->first(),
            ['*']
        );

        $order  =   Order::paid()->first();

        if ( $order instanceof Order ) {

            $order_id   =   $order->id;

            /**
             * @var OrdersService
             */
            $orderService   =   app()->make( OrdersService::class );
            $orderService->deleteOrder( $order );

            $totalPayments    =   OrderPayment::where( 'order_id', $order_id )->count();

            return $this->assertTrue( $totalPayments === 0, 
                sprintf(
                    __( 'An order payment hasn\'t been deleted along with the order (%s).' ),
                    $order->id
                )
            );
        }

        throw new Exception( __( 'No order where found to perform the test.' ) );
    }
}
