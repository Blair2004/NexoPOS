<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\TransactionActionRule;
use App\Services\TestService;
use Modules\NsGastro\Tests\TestCase;
use Tests\Traits\WithAccountingTest;
use Tests\Traits\WithAuthentication;
use Tests\Traits\WithOrderTest;

class AccountingOrderTest extends TestCase
{
    use WithAccountingTest, WithAuthentication, WithOrderTest;

    public function test_unpaid_order()
    {
        // we can fairly assume accounting is setup so far
        $this->attemptAuthenticate();

        /**
         * @var TestService $testService
         */
        $testService = app()->make( TestService::class );

        $response = $this->attemptCreateOrder( $testService->prepareOrder(
            config: [
                'payments' => fn() => [],
            ]
        ) );

        $response->assertOk();
        $order = $response->json()[ 'data' ][ 'order' ];

        $this->attemptTestAccountingForOrder( Order::findOrFail( $order[ 'id' ] ) );
    }

    public function test_paid_order()
    {
        // we can fairly assume accounting is setup so far
        $this->attemptAuthenticate();

        /**
         * @var TestService $testService
         */
        $testService = app()->make( TestService::class );

        $response = $this->attemptCreateOrder( $testService->prepareOrder() );

        $response->assertOk();
        $order = $response->json()[ 'data' ][ 'order' ];

        $this->attemptTestAccountingForOrder( Order::findOrFail( $order[ 'id' ] ) );
    }

    public function test_void_paid_order()
    {
        $this->attemptAuthenticate();

        /**
         * @var TestService $testService
         */
        $testService = app()->make( TestService::class );

        $response = $this->attemptCreateOrder( $testService->prepareOrder() );

        $order = $response->json()[ 'data' ][ 'order' ];
        $order = Order::findOrFail( $order[ 'id' ] );

        $this->attemptVoidOrder( $order );
        $this->attemptTestAccountingForVoidedOrder( $order, TransactionActionRule::RULE_ORDER_PAID_VOIDED );
    }

    public function test_void_unpaid_order()
    {
        $this->attemptAuthenticate();

        /**
         * @var TestService $testService
         */
        $testService = app()->make( TestService::class );

        $response = $this->attemptCreateOrder( $testService->prepareOrder(
            config: [
                'payments' => fn() => [],
            ]
        ) );

        $order = $response->json()[ 'data' ][ 'order' ];
        $order = Order::findOrFail( $order[ 'id' ] );

        $this->attemptVoidOrder( $order );
        $this->attemptTestAccountingForVoidedOrder( $order, TransactionActionRule::RULE_ORDER_UNPAID_VOIDED );
    }

    public function test_order_from_unpaid_to_paid()
    {
        $this->attemptAuthenticate();

        /**
         * @var TestService $testService
         */
        $testService = app()->make( TestService::class );

        $response = $this->attemptCreateOrder( $testService->prepareOrder(
            config: [
                'payments' => fn() => [],
            ]
        ) );

        $order = $response->json()[ 'data' ][ 'order' ];
        $order = Order::findOrFail( $order[ 'id' ] );

        $this->attemptCompleteOrderPayment( $order, OrderPayment::PAYMENT_CASH );

        $this->attemptTestAccountingForUnpaidToPaidOrder( $order );
    }

    public function test_delete_order()
    {
        $this->attemptAuthenticate();

        /**
         * @var TestService $testService
         */
        $testService = app()->make( TestService::class );

        $response = $this->attemptCreateOrder( $testService->prepareOrder() );

        $order = $response->json()[ 'data' ][ 'order' ];
        $order = Order::findOrFail( $order[ 'id' ] );

        $this->attemptDeleteOrder( $order );
        $this->attemptTestAccountingForDeletedOrder( $order );
    }

    public function test_order_cogs()
    {
        $this->attemptAuthenticate();

        /**
         * @var TestService $testService
         */
        $testService = app()->make( TestService::class );

        $response = $this->attemptCreateOrder( $testService->prepareOrder() );

        $order = $response->json()[ 'data' ][ 'order' ];
        $order = Order::findOrFail( $order[ 'id' ] );

        $this->attemptTestAccountingForOrderCogs( $order );
    }
}
