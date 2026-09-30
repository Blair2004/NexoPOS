<?php

namespace Tests\Unit;

use App\Crud\CustomerCrud;
use App\Http\Controllers\Dashboard\OrdersController;
use App\Models\Order;
use App\Services\DateService;
use App\Services\MarketplaceService;
use App\Services\Options;
use App\Services\OrdersService;
use Illuminate\Support\Facades\View;
use Mockery;
use Tests\TestCase;

class OrdersControllerTest extends TestCase
{
    public function test_order_invoice_uses_the_delivery_status_label(): void
    {
        $ordersService = Mockery::mock( OrdersService::class );
        $ordersService->shouldReceive( 'getPaymentLabel' )
            ->once()
            ->with( Order::PAYMENT_PAID )
            ->andReturn( 'Paid' );
        $ordersService->shouldReceive( 'getDeliveryStatus' )
            ->once()
            ->with( Order::DELIVERY_PENDING )
            ->andReturn( 'Pending' );

        $optionsService = Mockery::mock( Options::class );
        $optionsService->shouldReceive( 'get' )->once()->andReturn( [] );
        $this->app->instance( Options::class, $optionsService );

        $customerForm = [
            'tabs' => [
                'billing' => [ 'fields' => [] ],
                'shipping' => [ 'fields' => [] ],
            ],
        ];

        Mockery::mock( 'overload:' . CustomerCrud::class )
            ->shouldReceive( 'getForm' )
            ->once()
            ->andReturn( $customerForm );

        $order = Mockery::mock( Order::class )->makePartial();
        $order->payment_status = Order::PAYMENT_PAID;
        $order->delivery_status = Order::DELIVERY_PENDING;
        $order->code = 'TEST-ORDER';
        $order->products = collect();
        $order->shouldReceive( 'load' )->times( 6 )->andReturnSelf();

        View::shouldReceive( 'make' )
            ->once()
            ->withArgs( function ( string $view, array $data ): bool {
                $this->assertSame( 'pages.dashboard.orders.templates.invoice', $view );
                $this->assertSame( 'Paid', $data[ 'order' ]->paymentStatus );
                $this->assertSame( 'Pending', $data[ 'order' ]->deliveryStatus );

                return true;
            } )
            ->andReturn( 'invoice-view' );

        $controller = new OrdersController(
            $ordersService,
            $optionsService,
            Mockery::mock( DateService::class ),
            Mockery::mock( MarketplaceService::class )
        );

        $this->assertSame( 'invoice-view', $controller->orderInvoice( $order ) );
    }
}
