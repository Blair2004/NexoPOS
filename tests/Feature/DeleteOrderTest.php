<?php

namespace Tests\Feature;

use App\Models\CashFlow;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Role;
use App\Services\OrdersService;
use App\Services\ProductService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteOrderTest extends CreateOrderTest
{
    protected $count                =   1;
    protected $totalDaysInterval    =   1;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_delete_order()
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
        $products   =  $order->products
            ->filter( fn( $product ) => $product->product_id > 0 )
            ->map( function( $product ) use ( $productService ) {
            $product->previous_quantity   =   $productService->getQuantity( $product->product_id, $product->unit_id );
            return $product;
        });

        /**
         * let's check if the order has a cash flow entry
         */
        $this->assertTrue( CashFlow::where( 'order_id', $order->id )->first() instanceof CashFlow, 'No cash flow created for the order.' );

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

            /**
             * let's check if flow entry has been removed
             */
            $this->assertTrue( ! CashFlow::where( 'order_id', $order->id )->first() instanceof CashFlow, 'The cash flow hasn\'t been deleted.' );

            $products->each( function( OrderProduct $orderProduct ) use ( $productService ){
                $originalProduct                =   $orderProduct->product;

                if ( $originalProduct->stock_management === Product::STOCK_MANAGEMENT_ENABLED ) {
                    $orderProduct->actual_quantity   =   $productService->getQuantity( $orderProduct->product_id, $orderProduct->unit_id );
    
                    /**
                     * Let's check if the quantity has been restored 
                     * to the default value.
                     */
                    $this->assertTrue( 
                        ( float ) $orderProduct->actual_quantity == ( float ) $orderProduct->previous_quantity + ( float ) $orderProduct->quantity,
                        __( 'The new quantity was not restored to what it was before the deletion.')
                    );
                }
            });

        } else {
            throw new Exception( __( 'No order where found to perform the test.' ) );
        }

    }
}
