<?php

namespace Tests\Feature;

use App\Models\Order;
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
    public function testRefund()
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
}
