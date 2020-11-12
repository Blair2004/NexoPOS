<?php
namespace App\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterPaymentCreatedEvent;
use App\Events\OrderAfterProductRefundedEvent;
use App\Events\OrderBeforeDeleteEvent;
use App\Events\OrderBeforeDeleteProductEvent;
use App\Events\OrderBeforePaymentCreatedEvent;
use App\Jobs\ComputeCashierSalesJob;
use App\Jobs\ComputeCustomerAccountJob;
use App\Jobs\ComputeDayReportJob;
use App\Services\CustomerService;
use App\Services\OrdersService;
use App\Services\ProductService;

class OrderEventsSubscriber 
{
    private $ordersService;
    private $productsService;
    private $customerService;

    public function __construct(
        OrdersService $ordersService,
        ProductService $productsService,
        CustomerService $customerService
    ) {
        $this->ordersService    =   $ordersService;
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
            [ OrderEventsSubscriber::class, 'beforeDeleteOrder' ]
        );

        $events->listen(
            OrderBeforeDeleteProductEvent::class,
            [ OrderEventsSubscriber::class, 'beforeDeleteProductEvent' ]
        );

        $events->listen(
            OrderAfterCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'afterOrderCreated' ]
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
     * Handle cases when an order is about to be deleted
     * @param OrderBeforeDeleteEvent class
     */
    public function beforeDeleteOrder( OrderBeforeDeleteEvent $event )
    {
        ComputeDayReportJob::dispatch( $event )
            ->delay( now()->addSecond( 10 ) );

        ComputeCustomerAccountJob::dispatch( $event )
            ->delay( now()->addSecond( 10 ) );

        ComputeCashierSalesJob::dispatch( $event )
            ->delay( now()->addSecond(10) );
    }

    /**
     * listen when an order has
     * been created
     */
    public function afterOrderCreated( OrderAfterCreatedEvent $event )
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
        $this->customerService->canReduceCustomerAccount( $event->customer, $event->value );
    }

    /**
     * Listen when somebody try 
     * to delete an order product
     * @param OrderBeforeDeleteProductEvent $event
     */
    public function beforeDeleteProductEvent( OrderBeforeDeleteProductEvent $event )
    {
        
    }
}