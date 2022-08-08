<?php

namespace App\Listeners;

use App\Events\ProductAfterDeleteEvent;
use App\Services\ProductService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class ProductAfterDeleteEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(
        public ProductService $productService
    )
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\ProductAfterDeleteEvent  $event
     * @return void
     */
    public function handle(ProductAfterDeleteEvent $event)
    {
        //
    }
}
