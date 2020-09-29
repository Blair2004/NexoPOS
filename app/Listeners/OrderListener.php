<?php
namespace App\Listeners;

use App\OrderAfterCreatedEvent;
use App\Events\OrderBeforeDeleteProductEvent;
use App\Events\OrderAfterProductRefundedEvent;

class OrderListener 
{
    public function __construct(
        OrdersService $ordersService,
        ProductsService $productsService
    ) {
        $this->ordersService    =   $ordersService;
        $this->productsService  =   $productsService;
    }

    public function subscribe( $events )
    {
        $events->listen(
            OrderAfterProductRefundedEvent::class,
            [ OrderListener::class, 'refreshOrder' ]
        );

        $events->listen(
            OrderBeforeDeleteEvent::class,
            [ OrderListener::class, 'beforeDeleteOrder' ]
        );

        $events->listen(
            OrderBeforeDeleteProductEvent::class,
            [ OrderListener::class, 'beforeDeleteProductEvent' ]
        );

        $event->listen(
            OrderAfterCreatedEvent::class,
            [ OrderListener::class, 'afterOrderCreated' ]
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
        // 
    }

    /**
     * listen when an order has
     * been created
     */
    public function afterOrderCreated( OrderAfterCreatedEvent $event )
    {
        //
    }

    /**
     * Listen when somebody try 
     * to delete an order product
     * @param OrderBeforeDeleteProductEvent $event
     */
    public function beforeDeleteProductEvent( OrderBeforeDeleteProductEvent $event )
    {
        // 
    }
}