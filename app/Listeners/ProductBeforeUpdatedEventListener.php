<?php

namespace App\Listeners;

use App\Events\ProductBeforeUpdatedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
