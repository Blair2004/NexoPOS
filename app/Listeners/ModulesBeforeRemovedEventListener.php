<?php

namespace App\Listeners;

use App\Events\ModulesBeforeRemovedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

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
     * @param  object  $event
     * @return void
     */
    public function handle( ModulesBeforeRemovedEvent $event)
    {
        //
    }
}
