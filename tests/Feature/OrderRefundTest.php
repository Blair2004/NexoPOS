<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderProductRefund;
use App\Models\OrderRefund;
use App\Models\Product;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class OrderRefundTest extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_refund()
    {
        $this->attemptAuthenticate();

        $this->attemptRefundOrder(
            productQuantity: 5,
            refundQuantity: 5,
            paymentStatus: Order::PAYMENT_REFUNDED,
            message: 'The order wasn\'t marked as refunded after a refund.'
        );

        $this->attemptRefundOrder(
            productQuantity: 5,
            refundQuantity: 1,
            paymentStatus: Order::PAYMENT_PARTIALLY_REFUNDED,
            message: 'The order wasn\'t marked as partially refunded after a refund.'
        );
    }

    public function test_refund_rejects_negative_unit_price()
    {
        $this->attemptAuthenticate();

        $product = Product::notGrouped()->with( 'unit_quantities' )->firstOrFail();
        $unitQuantity = $product->unit_quantities->first();

        $response = $this->json( 'POST', 'api/orders', [
            'customer_id' => $this->attemptCreateCustomer()->id,
            'type' => [ 'identifier' => 'takeaway' ],
            'payment_status' => Order::PAYMENT_UNPAID,
            'shipping' => 0,
            'discount' => 0,
            'discount_percentage' => 0,
            'products' => [
                [
                    'product_id' => $product->id,
                    'unit_quantity_id' => $unitQuantity->id,
                    'quantity' => 1,
                    'unit_price' => $unitQuantity->sale_price,
                ],
            ],
        ] );

        $response->assertJsonPath( 'status', 'success' );

        $order = $response->json( 'data.order' );
        $orderProduct = $order[ 'products' ][ 0 ];

        $refundCount = OrderRefund::where( 'order_id', $order[ 'id' ] )->count();

        $response = $this->withSession( $this->app[ 'session' ]->all() )
            ->json( 'POST', 'api/orders/' . $order[ 'id' ] . '/refund', [
                'payment' => [
                    'identifier' => 'account-payment',
                ],
                'refund_shipping' => false,
                'products' => [
                    [
                        'id' => $orderProduct[ 'id' ],
                        'quantity' => 1,
                        'unit_price' => -1,
                        'condition' => OrderProductRefund::CONDITION_DAMAGED,
                    ],
                ],
            ] );

        $response->assertForbidden()->assertJsonPath( 'status', 'error' );
        $this->assertSame(
            $refundCount,
            OrderRefund::where( 'order_id', $order[ 'id' ] )->count(),
            'A refund was persisted even though the unit price was negative.'
        );
    }
}
