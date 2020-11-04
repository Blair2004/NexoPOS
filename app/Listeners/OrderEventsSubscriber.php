<?php
namespace App\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterProductRefundedEvent;
use App\Events\OrderBeforeDeleteEvent;
use App\Events\OrderBeforeDeleteProductEvent;
use App\Jobs\ComputeCashierSalesJob;
use App\Jobs\ComputeCustomerAccountJob;
use App\Jobs\ComputeDayReportJob;
use App\Services\OrdersService;
use App\Services\ProductService;

class OrderEventsSubscriber 
{
    private $ordersService;
    private $productsService;

    public function __construct(
        OrdersService $ordersService,
        ProductService $productsService
    ) {
        $this->ordersService    =   $ordersService;
        $this->productsService  =   $productsService;
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
     * Listen when somebody try 
     * to delete an order product
     * @param OrderBeforeDeleteProductEvent $event
     */
    public function beforeDeleteProductEvent( OrderBeforeDeleteProductEvent $event )
    {
        
    }
}