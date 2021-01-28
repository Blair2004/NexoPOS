<?php
namespace App\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterPaymentCreatedEvent;
use App\Events\OrderAfterProductRefundedEvent;
use App\Events\OrderAfterRefundedEvent;
use App\Events\OrderBeforeDeleteEvent;
use App\Events\OrderBeforeDeleteProductEvent;
use App\Events\OrderBeforePaymentCreatedEvent;
use App\Jobs\ComputeCashierSalesJob;
use App\Jobs\ComputeCustomerAccountJob;
use App\Jobs\ComputeDayReportJob;
use App\Models\OrderPayment;
use App\Services\CustomerService;
use App\Services\OrdersService;
use App\Services\ProductService;

class OrderEventsSubscriber 
{
    /**
     * @var OrdersService
     */
    private $ordersService;

    /**
     * @var ProductService
     */
    private $productsService;

    /**
     * @var CustomerService
     */
    private $customerService;

    public function __construct(
        OrdersService $ordersService,
        ProductService $productsService,
        CustomerService $customerService
    ) {
        $this->ordersService    =   app()->make( OrdersService::class );
        $this->productsService  =   $productsService;
        $this->customerService  =   $customerService;
    }

    public function subscribe( $events )
    {
        $events->listen(
            OrderAfterProductRefundedEvent::class,
            [ OrderEventsSubscriber::class, 'refreshOrder' ]
        );


        $events->listen(
            OrderBeforeDeleteEvent::class,
            [ OrderEventsSubscriber::class, 'handleOrderUpdate' ]
        );

        $events->listen(
            OrderBeforeDeleteProductEvent::class,
            [ OrderEventsSubscriber::class, 'beforeDeleteProductEvent' ]
        );

        $events->listen(
            OrderAfterCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'handleOrderUpdate' ]
        );

        $events->listen(
            OrderAfterPaymentCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'handleOrderUpdate' ]
        );

        $events->listen(
            OrderAfterPaymentCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'afterOrderPaymentCreated' ]
        );

        $events->listen(
            OrderBeforePaymentCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'beforePaymentCreated' ]
        );
    }

    /**
     * this will refresh an order
     * @param Event
     */
    public function refreshOrder( OrderAfterProductRefundedEvent $event ) 
    {
        $this->ordersService->refreshOrder( $event->order );
    }

    /**
     * listen when an order has
     * been created
     */
    public function handleOrderUpdate( $event )
    {
        ComputeDayReportJob::dispatch()
            ->delay( now()->addSecond(5) );

        ComputeCustomerAccountJob::dispatch( $event )
            ->delay( now()->addSecond(5) );

        ComputeCashierSalesJob::dispatch( $event )
            ->delay( now()->addSecond(10) );
    }

    /**
     * When a sales is made on a specific order
     * we to increase the customers purchases
     */
    public function afterOrderPaymentCreated( OrderAfterPaymentCreatedEvent $event )
    {
        $this->customerService->increaseOrderPurchases( 
            $event->order->customer, 
            $event->orderPayment->value 
        );
    }

    /**
     * Will check if the payment can be made on 
     * the customer account or will throw an error.
     * 
     * @param OrderBeforePaymentCreatedEvent $event
     * @return void
     */
    public function beforePaymentCreated( OrderBeforePaymentCreatedEvent $event )
    {
        if ( $event->payment[ 'identifier' ] === OrderPayment::PAYMENT_ACCOUNT ) {
            $this->customerService->canReduceCustomerAccount( $event->customer, $event->payment[ 'value' ] );
        }
    }

    public function handleRefundEvent( OrderAfterRefundedEvent $event )
    {
        $this->ordersService->refreshOrder( $event->order );
        $this->handleOrderUpdate( $event );
    }

    public function handleOrderAfterCreatedForCoupons( OrderAfterCreatedEvent $event ) 
    {
        $this->ordersService->trackOrderCoupons( $event->order );
    }
}