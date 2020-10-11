<?php
namespace App\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterProductRefundedEvent;
use App\Events\OrderBeforeDeleteEvent;
use App\Events\OrderBeforeDeleteProductEvent;
use App\Jobs\ComputeDayReportJob;
use App\Services\OrdersService;
use App\Services\ProductService;

class OrderListener 
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

        $events->listen(
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
        ComputeDayReportJob::dispatch()
            ->delay( now()->addMinute() );
    }

    /**
     * listen when an order has
     * been created
     */
    public function afterOrderCreated( OrderAfterCreatedEvent $event )
    {
        ComputeDayReportJob::dispatch()
            ->delay( now()->addMinute() );
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