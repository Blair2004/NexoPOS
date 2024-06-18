<?php

namespace App\Listeners;

use App\Events\NotificationDeletedEvent;

class NotificationListener
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

    public function handle( NotificationDeletedEvent $event )
    {
        // ...
    }
}
