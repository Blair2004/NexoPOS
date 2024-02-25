<?php

namespace Tests\Traits;

use App\Models\Order;
use App\Models\Product;
use App\Services\TestService;

trait WithCombinedProductTest
{
    protected function attemptCombineProducts()
    {
        ns()->option->set( 'ns_invoice_merge_similar_products', 'yes' );

        $testService = new TestService;
        $orderDetails = $testService->prepareOrder(
            date: ns()->date->now(),
            orderDetails: [],
            productDetails: [],
            config: [
                'products' => function () {
                    $product = Product::where( 'tax_group_id', '>', 0 )
                        ->whereRelation( 'unit_quantities', 'quantity', '>', 100 )
                        ->with( 'unit_quantities', fn( $query ) => $query->where( 'quantity', '>', 100 ) )
                        ->first();

                    return collect( [ $product, $product ] );
                },
                'allow_quick_products' => false,
            ]
        );

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/orders', $orderDetails );

        $response->assertStatus( 200 );

        $json = json_decode( $response->getContent() );
        $orderId = $json->data->order->id;

        $this->assertEquals( 1, Order::find( $orderId )->combinedProducts->count(), __( 'The product were\'nt combined.' ) );
    }
}
