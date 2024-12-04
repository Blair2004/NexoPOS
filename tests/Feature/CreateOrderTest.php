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
    public function test_posting_order( $callback = null )
    {
        $this->count = 1;
        $this->totalDaysInterval = 1;

        if ( $this->defaultProcessing ) {
            $this->attemptAuthenticate();

            return $this->attemptPostOrder( $callback );
        } else {
            $this->assertTrue( true ); // because we haven't performed any test.
        }
    }

    public function test_create_and_edit_order_with_low_stock()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateAndEditOrderWithLowStock();
    }

    public function test_create_and_edit_order_by_deducted_greater_quantity()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateAndEditOrderWithGreaterQuantity();
    }

    public function test_hold_and_checkout_order()
    {
        $this->attemptAuthenticate();
        $this->attemptHoldAndCheckoutOrder();
    }

    public function test_hold_and_checkout_order_with_grouped_products()
    {
        $this->attemptAuthenticate();
        $this->attemptHoldOrderAndCheckoutWithGroupedProducts();
    }

    public function test_deleted_voided_order()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteVoidedOrder();
    }

    /**
     * Will only make order using
     * the customer balance
     */
    public function test_order_created_for_customer()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateOrderPaidWithCustomerBalance();
    }

    public function test_create_order_with_no_payment( $callback = null )
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

    public function test_create_order_with_grouped_products()
    {
        $this->attemptAuthenticate();
        $this->attemptCreateOrderWithGroupedProducts();
    }

    /**
     * @depends test_create_order_with_grouped_products
     */
    public function test_refund_order_with_grouped_products()
    {
        $this->attemptAuthenticate();
        $this->attemptRefundOrderWithGroupedProducts();
    }

    public function test_delete_order_and_check_product_history()
    {
        $this->attemptAuthenticate();
        $this->attemptDeleteOrderAndCheckProductHistory();
    }
}
