<?php

namespace App\Listeners;

use App\Crud\OrderCrud;
use App\Crud\ProductCrud;
use App\Crud\ProductHistoryCrud;
use App\Events\RenderCrudTableFooterEvent;

class RenderCrudTableFooterEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( RenderCrudTableFooterEvent $event ): void
    {
        match ( $event->instance::class ) {
            OrderCrud::class => $event->output->addView( 'pages.dashboard.orders.footer' ),
            ProductCrud::class => $event->output->addView( 'pages.dashboard.products.quantity-popup' ),
            ProductHistoryCrud::class => $event->output->addView( 'pages.dashboard.products.history' ),
            default => null,
        };
    }
}
