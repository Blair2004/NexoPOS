<?php

namespace App\Listeners;

use App\Events\NotificationDeletedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
        $event->notification->delete();
    }
}
