<?php

namespace Tests\Unit;

use App\Models\Customer;
use App\Models\Order;
use App\Services\ZaloOaService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZaloOaServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config( [
            'services.zalo.enabled' => true,
            'services.zalo.dry_run' => true,
            'services.zalo.oa_id' => 'test-oa-id',
            'services.zalo.access_token' => 'test-access-token',
        ] );
    }

    private function makeOrder( bool $linked = true ): Order
    {
        $customer = new Customer;
        $customer->id = 1;
        $customer->first_name = 'An';
        $customer->last_name = 'Nguyen';
        $customer->zalo_user_id = $linked ? 'zalo-user-123' : null;

        $order = new Order;
        $order->id = 1;
        $order->code = 'TEST-001';
        $order->total = 150000;
        $order->payment_status = Order::PAYMENT_PAID;
        $order->process_status = Order::PROCESSING_READY;
        $order->setRelation( 'customer', $customer );

        return $order;
    }

    public function test_builds_order_confirmation_message(): void
    {
        $service = new ZaloOaService;
        $message = $service->buildOrderConfirmationMessage( $this->makeOrder() );

        $this->assertStringContainsString( 'TEST-001', $message );
        $this->assertStringContainsString( 'An Nguyen', $message );
        $this->assertStringContainsString( '150', $message );
    }

    public function test_builds_payment_and_invoice_messages(): void
    {
        $service = new ZaloOaService;
        $order = $this->makeOrder();

        $this->assertStringContainsString( 'TEST-001', $service->buildPaymentMessage( $order ) );
        $this->assertStringContainsString( 'TEST-001', $service->buildInvoiceMessage( $order ) );
        $this->assertStringContainsString( '150', $service->buildInvoiceMessage( $order ) );
    }

    public function test_builds_order_status_message(): void
    {
        $service = new ZaloOaService;
        $message = $service->buildOrderStatusMessage( $this->makeOrder() );

        $this->assertStringContainsString( 'TEST-001', $message );
    }

    public function test_builds_promotion_message(): void
    {
        $service = new ZaloOaService;
        $customer = $this->makeOrder()->customer;

        $message = $service->buildPromotionMessage( $customer, 'Giam 20% hom nay' );

        $this->assertStringContainsString( 'An Nguyen', $message );
        $this->assertStringContainsString( 'Giam 20% hom nay', $message );
    }

    public function test_dry_run_returns_simulated_result_without_http(): void
    {
        Http::fake();

        $service = new ZaloOaService;
        $result = $service->sendTextMessage( 'zalo-user-123', 'Hello' );

        $this->assertTrue( $result[ 'sent' ] );
        $this->assertTrue( $result[ 'dry_run' ] );

        Http::assertNothingSent();
    }

    public function test_disabled_service_does_not_send(): void
    {
        Http::fake();

        config( [ 'services.zalo.enabled' => false ] );

        $service = new ZaloOaService;
        $result = $service->sendTextMessage( 'zalo-user-123', 'Hello' );

        $this->assertFalse( $result[ 'sent' ] );
        $this->assertSame( 'disabled', $result[ 'reason' ] );

        Http::assertNothingSent();
    }

    public function test_order_confirmation_skipped_when_customer_not_linked(): void
    {
        Http::fake();

        $service = new ZaloOaService;
        $result = $service->sendOrderConfirmation( $this->makeOrder( linked: false ) );

        $this->assertFalse( $result[ 'sent' ] );
        $this->assertSame( 'no_recipient', $result[ 'reason' ] );

        Http::assertNothingSent();
    }

    public function test_live_send_posts_to_zalo_api(): void
    {
        Http::fake( [
            'openapi.zalo.me/*' => Http::response( [ 'error' => 0, 'message' => 'Success' ], 200 ),
        ] );

        config( [ 'services.zalo.dry_run' => false ] );

        $service = new ZaloOaService;
        $result = $service->sendTextMessage( 'zalo-user-123', 'Hello' );

        $this->assertTrue( $result[ 'sent' ] );
        $this->assertFalse( $result[ 'dry_run' ] );

        Http::assertSent( function ( $request ) {
            return $request->url() === 'https://openapi.zalo.me/v3.0/oa/message/cs'
                && $request->data()[ 'recipient' ][ 'user_id' ] === 'zalo-user-123'
                && $request->data()[ 'message' ][ 'text' ] === 'Hello';
        } );
    }
}
