<?php

namespace Tests\Feature;

use App\Models\Order;
use Tests\TestCase;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class CreateOrderTest extends TestCase
{
    use WithAuthentication, WithOrderTest;

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testPostingOrder( $callback = null )
    {
        $this->count = 2;
        $this->totalDaysInterval = 3;

        if ( $this->defaultProcessing ) {
            $this->attemptAuthenticate();

            return $this->attemptPostOrder( $callback );
        } else {
            $this->assertTrue( true ); // because we haven't performed any test.
        }
    }

    public function testCreateAndEditOrderWithLowStock()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateAndEditOrderWithLowStock();
    }

    public function testCreateAndEditOrderByDeductedGreaterQuantity()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateAndEditOrderWithGreaterQuantity();
    }

    public function testHoldAndCheckoutOrder()
    {
        $this->attemptAuthenticate();
        $this->attemptHoldAndCheckoutOrder();
    }

    public function testHoldAndCheckoutOrderWithGroupedProducts()
    {
        $this->attemptAuthenticate();
        $this->attemptHoldOrderAndCheckoutWithGroupedProducts();
    }

    public function testDeletedVoidedOrder()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteVoidedOrder();
    }

    /**
     * Will only make order using
     * the customer balance
     */
    public function testOrderCreatedForCustomer()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateOrderPaidWithCustomerBalance();
    }

    public function testCreateOrderWithNoPayment( $callback = null )
    {
        if ( $this->defaultProcessing ) {
            $this->attemptAuthenticate();

            $this->count = 1;
            $this->totalDaysInterval = 1;
            $this->processCoupon = false;
            $this->useDiscount = false;
            $this->shouldMakePayment = false;
            $this->customProductParams = [
                'unit_price' => 0,
                'discount' => 0,
            ];

            $responses = $this->attemptPostOrder( $callback );

            $this->assertEquals( Order::PAYMENT_UNPAID, $responses[0][0][ 'order-creation' ][ 'data' ][ 'order' ][ 'payment_status' ] );
        } else {
            $this->assertTrue( true ); // because we haven't performed any test.
        }
    }

    public function testCreateOrderWithGroupedProducts()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateOrderWithGroupedProducts();
    }

    /**
     * @depends testCreateOrderWithGroupedProducts
     */
    public function testRefundOrderWithGroupedProducts()
    {
        $this->attemptAuthenticate();
        $this->attemptRefundOrderWithGroupedProducts();
    }

    public function testDeleteOrderAndCheckProductHistory()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteOrderAndCheckProductHistory();
    }
}
