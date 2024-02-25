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
    public function handle( ProductBeforeUpdatedEvent $event ): void
    {
        $original = $event->product->getOriginal();

        session()->put( 'product_category_id', $original[ 'category_id' ] );
    }
}
