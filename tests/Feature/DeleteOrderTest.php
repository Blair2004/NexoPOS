<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Role;
use App\Services\OrdersService;
use App\Services\ProductService;
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

        /**
         * @var ProductService
         */
        $productService     =   app()->make( ProductService::class );

        $order      =   Order::paid()->first();
        $products   =  $order->products->map( function( $product ) use ( $productService ) {
            $product->previous_quantity   =   $productService->getQuantity( $product->product_id, $product->unit_id );
            return $product;
        });

        if ( $order instanceof Order ) {

            $order_id   =   $order->id;

            /**
             * @var OrdersService
             */
            $orderService   =   app()->make( OrdersService::class );
            $orderService->deleteOrder( $order );

            $totalPayments    =   OrderPayment::where( 'order_id', $order_id )->count();

            $this->assertTrue( $totalPayments === 0, 
                sprintf(
                    __( 'An order payment hasn\'t been deleted along with the order (%s).' ),
                    $order->id
                )
            );

            $products->each( function( $product ) use ( $productService ){
                $product->actual_quantity   =   $productService->getQuantity( $product->product_id, $product->unit_id );
                
                /**
                 * Let's check if the quantity has been restored 
                 * to the default value.
                 */
                $this->assertTrue( 
                    $product->actual_quantity == $product->previous_quantity + $product->quantity,
                    __( 'The new quantity was not restored to what it was before the deletion.')
                );
            });

        } else {
            throw new Exception( __( 'No order where found to perform the test.' ) );
        }

    }
}
