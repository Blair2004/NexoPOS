<?php

namespace App\Listeners;

use App\Events\OrderBeforeDeleteEvent;

class OrderBeforeDeleteEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle(OrderBeforeDeleteEvent $event)
    {
        // ...
    }
}
