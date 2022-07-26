<?php

namespace App\Listeners;

use App\Events\OrderAfterCreatedEvent;
use App\Events\OrderAfterPaymentCreatedEvent;
use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Events\OrderAfterProductRefundedEvent;
use App\Events\OrderAfterRefundedEvent;
use App\Events\OrderAfterUpdatedEvent;
use App\Events\OrderBeforeDeleteEvent;
use App\Events\OrderBeforeDeleteProductEvent;
use App\Events\OrderBeforePaymentCreatedEvent;
use App\Events\OrderVoidedEvent;
use App\Jobs\ComputeCashierSalesJob;
use App\Jobs\ComputeCustomerAccountJob;
use App\Jobs\ComputeDayReportJob;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Services\CustomerService;
use App\Services\OrdersService;
use App\Services\ProductService;

class OrderEventsSubscriber
{

    public function __construct(
        protected OrdersService $ordersService,
        protected ProductService $productsService,
        protected CustomerService $customerService
    ) {
        // ...
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

        /**
         * all events likely to affect
         * the customer account.
         */
        $events->listen(
            OrderAfterCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'handleCustomerUpdates' ]
        );
        $events->listen(
            OrderAfterUpdatedEvent::class,
            [ OrderEventsSubscriber::class, 'handleCustomerUpdates' ]
        );
        $events->listen(
            OrderBeforeDeleteEvent::class,
            [ OrderEventsSubscriber::class, 'handleCustomerUpdates' ]
        );
        $events->listen(
            OrderAfterRefundedEvent::class,
            [ OrderEventsSubscriber::class, 'handleCustomerUpdates' ]
        );

        $events->listen(
            OrderAfterCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'handleInstalmentPayment' ]
        );

        $events->listen(
            OrderAfterCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'handleOrderAfterCreatedForCoupons' ]
        );

        $events->listen(
            OrderAfterUpdatedEvent::class,
            [ OrderEventsSubscriber::class, 'handleInstalmentPayment' ]
        );

        $events->listen(
            OrderAfterPaymentCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'handleOrderUpdate' ]
        );

        $events->listen(
            OrderAfterPaymentStatusChangedEvent::class,
            [ OrderEventsSubscriber::class, 'orderAfterPaymentStatusChangedEvent' ]
        );

        $events->listen(
            OrderAfterRefundedEvent::class,
            [ OrderEventsSubscriber::class, 'orderAfterRefundEvent' ]
        );

        $events->listen(
            OrderVoidedEvent::class,
            [ OrderEventsSubscriber::class, 'orderAfterVoidedEvent' ]
        );

        $events->listen(
            OrderAfterCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'orderAfterCreatedEvent' ]
        );

        $events->listen(
            OrderAfterUpdatedEvent::class,
            [ OrderEventsSubscriber::class, 'orderAfterCreatedEvent' ]
        );

        $events->listen(
            OrderBeforePaymentCreatedEvent::class,
            [ OrderEventsSubscriber::class, 'beforePaymentCreated' ]
        );
    }

    /**
     * this will refresh an order
     *
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

        ComputeCashierSalesJob::dispatch( $event )
            ->delay( now()->addSecond(10) );
    }

    public function handleCustomerUpdates( $event )
    {
        if (
            $event instanceof OrderAfterCreatedEvent ||
            $event instanceof OrderAfterUpdatedEvent ||
            $event instanceof OrderBeforeDeleteEvent ||
            $event instanceof OrderAfterRefundedEvent

        ) {
            ComputeCustomerAccountJob::dispatch(
                $event,
                app()->make( CustomerService::class )
            )
            ->delay( now()->addSecond(5) );
        }
    }

    /**
     * When an order is refunded
     * we'll gradually reduce the customer purchases
     */
    public function orderAfterRefundEvent( OrderAfterRefundedEvent $event )
    {
        if ( in_array( $event->order->payment_status, [
            Order::PAYMENT_PAID
        ]) ) {
            $this->customerService->decreasePurchases(
                $event->order->customer,
                $event->orderRefund->total
            );
        }
    }

    /**
     * When an order is voided
     * we'll gradually reduce the customer purchases
     */
    public function orderAfterVoidedEvent( OrderVoidedEvent $event )
    {
        if ( in_array( $event->order->payment_status, [
            Order::PAYMENT_PAID
        ]) ) {
            $this->customerService->decreasePurchases(
                $event->order->customer,
                $event->orderRefund->total
            );
        }
    }

    /**
     * When a sales is made on a specific order
     * we to increase the customers purchases
     */
    public function orderAfterPaymentStatusChangedEvent( OrderAfterPaymentStatusChangedEvent $event )
    {
        if ( in_array( $event->new, [
            Order::PAYMENT_PAID
        ]) ) {
            $this->customerService->increasePurchases(
                $event->order->customer,
                $event->order->total
            );
        }
    }

    /**
     * When an order is flagged as paid
     * yet after payment or update
     */
    public function orderAfterCreatedEvent( $event )
    {
        if ( in_array( $event->order->payment_status, [
            Order::PAYMENT_PAID
        ]) ) {
            $this->customerService->increasePurchases(
                $event->order->customer,
                $event->order->total
            );
        }
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

    public function handleInstalmentPayment( $event )
    {
        $this->ordersService->resolveInstalments( $event->order );
    }
}
