<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductHistory;
use App\Services\ProductService;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;

class NexoCommerceInventoryTest extends TestCase
{
    use WithAuthentication;

    public function test_tracked_standard_product_sale_deducts_stock_and_records_history(): void
    {
        $this->attemptAuthenticate();

        $product = Product::query()
            ->withStockEnabled()
            ->where( 'type', Product::TYPE_MATERIALIZED )
            ->notGrouped()
            ->notInGroup()
            ->whereHas( 'unit_quantities' )
            ->with( 'unit_quantities' )
            ->orderBy( 'id' )
            ->firstOrFail();
        $unitQuantity = $product->unit_quantities->sortBy( 'id' )->first();
        $productService = app( ProductService::class );
        $startingQuantity = 5;
        $soldQuantity = 2;
        $unitPrice = 25;

        $productService->setQuantity(
            product_id: $product->id,
            unit_id: $unitQuantity->unit_id,
            quantity: $startingQuantity,
        );

        $response = $this->withSession( $this->app['session']->all() )
            ->postJson( 'api/orders', [
                'customer_id' => Customer::query()->orderBy( 'id' )->firstOrFail()->id,
                'type' => ['identifier' => 'takeaway'],
                'discount_type' => 'flat',
                'discount_percentage' => 0,
                'discount' => 0,
                'shipping' => 0,
                'addresses' => [
                    'shipping' => [
                        'first_name' => 'Commerce',
                        'last_name' => 'Customer',
                        'country' => 'Cameroon',
                    ],
                    'billing' => [
                        'first_name' => 'Commerce',
                        'last_name' => 'Customer',
                        'country' => 'Cameroon',
                    ],
                ],
                'subtotal' => $unitPrice * $soldQuantity,
                'products' => [[
                    'product_id' => $product->id,
                    'quantity' => $soldQuantity,
                    'unit_price' => $unitPrice,
                    'tax_type' => 'inclusive',
                    'tax_group_id' => $product->tax_group_id,
                    'unit_id' => $unitQuantity->unit_id,
                    'unit_quantity_id' => $unitQuantity->id,
                ]],
                'payments' => [[
                    'identifier' => OrderPayment::PAYMENT_CASH,
                    'value' => $unitPrice * $soldQuantity,
                ]],
            ] );

        $response->assertOk()->assertJsonPath( 'status', 'success' );

        $order = Order::query()
            ->with( 'products' )
            ->findOrFail( $response->json( 'data.order.id' ) );
        $orderProduct = $order->products->sole();
        $historyQuery = ProductHistory::query()
            ->where( 'order_id', $order->id )
            ->where( 'order_product_id', $orderProduct->id )
            ->where( 'operation_type', ProductHistory::ACTION_SOLD );

        $this->assertSame( Order::PAYMENT_PAID, $order->payment_status );
        $this->assertSame( 1, $historyQuery->count() );

        $history = $historyQuery->sole();

        $this->assertSame( $product->id, $history->product_id );
        $this->assertSame( $unitQuantity->unit_id, $history->unit_id );
        $this->assertEquals( $soldQuantity, $orderProduct->quantity );
        $this->assertEquals( $soldQuantity, $history->quantity );
        $this->assertEquals( $startingQuantity, $history->before_quantity );
        $this->assertEquals( $startingQuantity - $soldQuantity, $history->after_quantity );
        $this->assertEquals( $orderProduct->unit_price, $history->unit_price );
        $this->assertEquals( $orderProduct->total_price, $history->total_price );
        $this->assertEquals(
            $startingQuantity - $soldQuantity,
            $productService->getQuantity( $product->id, $unitQuantity->unit_id ),
        );
    }

    public function test_second_order_cannot_sell_the_last_tracked_unit_twice(): void
    {
        $this->attemptAuthenticate();

        $product = Product::query()
            ->withStockEnabled()
            ->where( 'type', Product::TYPE_MATERIALIZED )
            ->notGrouped()
            ->notInGroup()
            ->whereHas( 'unit_quantities' )
            ->with( 'unit_quantities' )
            ->orderBy( 'id' )
            ->firstOrFail();
        $unitQuantity = $product->unit_quantities->sortBy( 'id' )->first();
        $productService = app( ProductService::class );
        $unitPrice = 25;

        $productService->setQuantity(
            product_id: $product->id,
            unit_id: $unitQuantity->unit_id,
            quantity: 1,
        );

        $orderPayload = [
            'customer_id' => Customer::query()->orderBy( 'id' )->firstOrFail()->id,
            'type' => ['identifier' => 'takeaway'],
            'discount_type' => 'flat',
            'discount_percentage' => 0,
            'discount' => 0,
            'shipping' => 0,
            'addresses' => [
                'shipping' => [
                    'first_name' => 'Commerce',
                    'last_name' => 'Customer',
                    'country' => 'Cameroon',
                ],
                'billing' => [
                    'first_name' => 'Commerce',
                    'last_name' => 'Customer',
                    'country' => 'Cameroon',
                ],
            ],
            'subtotal' => $unitPrice,
            'products' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $unitPrice,
                'tax_type' => 'inclusive',
                'tax_group_id' => $product->tax_group_id,
                'unit_id' => $unitQuantity->unit_id,
                'unit_quantity_id' => $unitQuantity->id,
            ]],
            'payments' => [[
                'identifier' => OrderPayment::PAYMENT_CASH,
                'value' => $unitPrice,
            ]],
        ];

        $firstResponse = $this->withSession( $this->app['session']->all() )
            ->postJson( 'api/orders', $orderPayload );

        $firstResponse->assertOk()->assertJsonPath( 'status', 'success' );

        $ordersAfterFirstSale = Order::query()->count();
        $saleHistoriesAfterFirstSale = ProductHistory::query()
            ->where( 'product_id', $product->id )
            ->where( 'unit_id', $unitQuantity->unit_id )
            ->where( 'operation_type', ProductHistory::ACTION_SOLD )
            ->count();

        $this->assertEquals( 0, $productService->getQuantity( $product->id, $unitQuantity->unit_id ) );

        $secondResponse = $this->withSession( $this->app['session']->all() )
            ->postJson( 'api/orders', $orderPayload );

        $secondResponse->assertStatus( 500 );
        $this->assertSame( $ordersAfterFirstSale, Order::query()->count() );
        $this->assertSame(
            $saleHistoriesAfterFirstSale,
            ProductHistory::query()
                ->where( 'product_id', $product->id )
                ->where( 'unit_id', $unitQuantity->unit_id )
                ->where( 'operation_type', ProductHistory::ACTION_SOLD )
                ->count(),
        );
        $this->assertEquals( 0, $productService->getQuantity( $product->id, $unitQuantity->unit_id ) );
    }
}
