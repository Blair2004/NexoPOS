<?php

namespace App\Listeners;

use App\Events\UserAfterUpdatedEvent;

class UserAfterUpdatedEventListener
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
    public function handle( UserAfterUpdatedEvent $event ): void
    {
        //
    }
}
