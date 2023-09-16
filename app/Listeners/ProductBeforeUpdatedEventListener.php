<?php

namespace App\Listeners;

use App\Events\ProductBeforeUpdatedEvent;

class ProductBeforeUpdatedEventListener
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
    public function handle( ProductBeforeUpdatedEvent $event): void
    {
        session()->put( 'product_category_id', $event->product->category_id );
    }
}
