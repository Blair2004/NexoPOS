<?php

namespace App\Listeners;

use App\Events\ModulesBeforeRemovedEvent;

class ModulesBeforeRemovedEventListener
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
     * @param  object $event
     * @return void
     */
    public function handle( ModulesBeforeRemovedEvent $event )
    {
        //
    }
}
