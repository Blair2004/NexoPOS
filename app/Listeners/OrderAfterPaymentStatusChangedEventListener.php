<?php

namespace App\Listeners;

use App\Events\OrderAfterPaymentStatusChangedEvent;
use App\Models\Order;
use App\Services\CashRegistersService;
use App\Services\DriverService;
use App\Services\OrdersService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class OrderAfterPaymentStatusChangedEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct(
        public TransactionService $transactionService,
        public OrdersService $ordersService,
        public CashRegistersService $cashRegistersService,
        public DriverService $driverService
    ) {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( OrderAfterPaymentStatusChangedEvent $event ): void
    {
        /**
         * Step: Order Directly Paid
         * when the order is directly paid or directly unpaid we handle the transactions directly
         */
        if (
            (
                $event->previous === null &&
                $event->new === Order::PAYMENT_PAID
            ) ||
            (
                $event->previous === null &&
                $event->new === Order::PAYMENT_UNPAID
            )
        ) {
            $this->transactionService->handleSaleTransaction( $event->order );

            /**
             * if the order is yet paid
             * we can compute the cost of goods sold
             */
            if ( $event->new === Order::PAYMENT_PAID ) {
                $this->transactionService->handleCogsFromSale( $event->order );
            }
        }

        /**
         * We want to decide from when the inventory
         * is moved out to the customer destination. And order that is partially paid start holding the
         * inventory for the customer. In case there is no sale in due time, the inventory might be returned.
         */
        if (
            in_array( $event->previous, [ null, Order::PAYMENT_HOLD, Order::PAYMENT_UNPAID ] )
            && in_array( $event->new, [ Order::PAYMENT_PAID, Order::PAYMENT_PARTIALLY ] ) ) {
            $this->ordersService->saveOrderProductHistory( $event->order );
        }

        /**
         * Step: Order From Unpaid to Paid
         * when the order payment status is changed we handle the transactions
         */
        if (
            in_array( $event->previous, [
                Order::PAYMENT_UNPAID,
                Order::PAYMENT_HOLD,
                Order::PAYMENT_PARTIALLY,
            ] ) &&
            $event->new === Order::PAYMENT_PAID
        ) {
            $this->transactionService->handleUnpaidToPaidSaleTransaction( $event->order );
            $this->ordersService->saveOrderProductHistory( $event->order );
        }

        /**
         * Step: Order from Paid to Void
         */
        if (
            $event->previous === Order::PAYMENT_PAID &&
            $event->new === Order::PAYMENT_VOID
        ) {
            $this->transactionService->handlePaidToVoidSaleTransaction( $event->order );
            $this->ordersService->returnVoidProducts( $event->order );
        }

        /**
         * Step: Order from Unpaid to Void
         */
        if (
            in_array( $event->previous, [
                Order::PAYMENT_UNPAID,
                Order::PAYMENT_PARTIALLY,
            ] ) &&
            $event->new === Order::PAYMENT_VOID
        ) {
            $this->transactionService->handleUnpaidToVoidSaleTransaction( $event->order );
            $this->ordersService->returnVoidProducts( $event->order );
        }

        /**
         * Handle driver commission when order moves from ongoing to delivered
         */
        $this->handleDriverCommission($event);
    }

    /**
     * Handle driver commission for delivery completion
     *
     * @param OrderAfterPaymentStatusChangedEvent $event
     */
    protected function handleDriverCommission(OrderAfterPaymentStatusChangedEvent $event): void
    {
        $order = $event->order;
        
        // Check if this is a delivery order with a driver
        if (!$order->driver_id || $order->type !== Order::TYPE_DELIVERY) {
            return;
        }

        // Check if order moves to paid status and is marked as delivered
        if (
            $order->delivery_status === Order::DELIVERY_DELIVERED &&
            $event->new === Order::PAYMENT_PAID &&
            in_array($event->previous, [Order::PAYMENT_UNPAID, Order::PAYMENT_PARTIALLY])
        ) {
            try {
                $this->driverService->createEarningForDelivery($order, $order->driver_id);
            } catch (\Exception $e) {
                // Log the error but don't fail the payment process
                Log::warning('Failed to create driver commission: ' . $e->getMessage(), [
                    'order_id' => $order->id,
                    'driver_id' => $order->driver_id
                ]);
            }
        }
    }
}
