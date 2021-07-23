<?php
namespace App\Listeners;

use App\Events\ProcurementAfterCreateEvent;
use App\Events\ProcurementAfterDeleteEvent;
use App\Events\ProcurementAfterUpdateEvent;
use App\Services\ProductService;
use App\Services\ProcurementService;
use App\Events\ProcurementCancelationEvent;
use App\Events\ProcurementBeforeDeleteEvent;
use App\Events\ProcurementBeforeUpdateEvent;
use App\Events\ProcurementBeforeUpdateProductEvent;
use App\Models\Provider;
use App\Services\ExpenseService;
use App\Services\ProviderService;

class ProcurementEventsSubscriber
{
    protected $procurementService;
    protected $providerService;
    protected $productService;

    /**
     * @var ExpenseService
     */
    protected $expenseService;

    public function __construct( 
        ProcurementService $procurementService,
        ProductService $productService,
        ProviderService $providerService,
        ExpenseService $expenseService
    ) {
        $this->procurementService   =   $procurementService;
        $this->providerService      =   $providerService;
        $this->productService       =   $productService;
        $this->expenseService       =   $expenseService;
    }

    public function subscribe( $events )
    {
        $events->listen(
            ProcurementAfterCreateEvent::class,
            fn( $event ) => $this->procurementService->refresh( $event->procurement )
        );

        /**
         * this will compute the provider
         * summary when a procurement is being created
         */
        $events->listen(
            ProcurementAfterCreateEvent::class,
            fn( $event ) => $this->providerService->computeSummary( $event->procurement->provider )
        );

        /**
         * This will record an history
         * only when the created procurement as his 
         * status set to delivered
         */
        $events->listen(
            ProcurementAfterCreateEvent::class,
            fn( $event ) => $this->procurementService->handleProcurement( $event->procurement )
        );

        /**
         * This will cancel the payment made on the provider
         * that was assigned to the procurement
         */
        $events->listen( 
            ProcurementBeforeUpdateEvent::class,
            fn( $event ) => $this->providerService->cancelPaymentForProcurement( $event->procurement )
        );

        /**
         * This will compute the 
         * value of the procurement (total_items, value, tax_value )
         */
        $events->listen(
            ProcurementAfterUpdateEvent::class,
            fn( $event ) => $this->procurementService->refresh( $event->procurement )
        );

        /**
         * This will recompute the provider assigned to the procurement
         * summary (amount_paid & amount_due)
         */
        $events->listen( 
            ProcurementAfterUpdateEvent::class,
            fn( $event ) => $this->providerService->computeSummary( $event->procurement->provider )
        );

        /**
         * This will record an history
         * only when the updated procurement as his 
         * status set to delivered
         */
        $events->listen(
            ProcurementAfterUpdateEvent::class,
            fn( $event ) => $this->procurementService->handleProcurement( $event->procurement )
        );

        /**
         * if a procurement is saved as paid
         * then we'll create an expense
         */
        $events->listen(
            ProcurementAfterUpdateEvent::class,
            fn( ProcurementAfterUpdateEvent $event ) => $this->expenseService->handleProcurementExpense( $event->procurement )
        );

        /**
         * We'll check if the procurement
         * after being created is marked as paid.
         */
        $events->listen(
            ProcurementAfterCreateEvent::class,
            fn( ProcurementAfterCreateEvent $event ) => $this->expenseService->handleProcurementExpense( $event->procurement )
        );

        /**
         * @deprecated
         */
        $events->listen(
            ProcurementCancelationEvent::class,
            fn( $event ) => $this->procurementService->refresh( $event->procurement )
        );

        /**
         * this will helps to remove the
         * stock which has been previously 
         * provided on the product
         * @param ProcurementBeforeUpdateProductEvent
         * @return void
         */
        $events->listen(
            ProcurementBeforeUpdateProductEvent::class,
            fn( $event ) => null
        );

        $events->listen(
            ProcurementAfterUpdateProductEvent::class,
            fn( $event ) => $this->productService->procurementStockEntry( $event->product, $event->fields )
        );

        $events->listen(
            ProcurementAfterUpdateProductEvent::class,
            fn( $event ) => $this->procurementService->refresh( $event->product->procurement )
        );

        $events->listen(
            ProcurementBeforeDeleteProductEvent::class,
            fn( $event ) => null
        );

        $events->listen(
            ProcurementAfterDeleteProductEvent::class,
            fn( $event ) => $this->procurementService->refresh( $event->procurement_id )
        );

        $events->listen(
            ProcurementAfterDeleteEvent::class,
            fn( $event ) => $this->providerService->computeSummary( 
                Provider::find( $event->procurement_data[ 'provider_id' ] ) 
            )
        );

        $events->listen(
            ProcurementBeforeDeleteEvent::class,
            fn( $event ) => $this->procurementService->attemptProductsStockRemoval( $event->procurement ),
        );

        /**
         * This will delete all the product that are
         * assigned to a procurement before deleting the procurement itself
         */
        $events->listen(
            ProcurementBeforeDeleteEvent::class,
            fn( $event ) => $this->procurementService->deleteProcurementProducts( $event->procurement ),
        );
    }
}
