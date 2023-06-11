<?php

namespace App\Listeners;

use App\Events\ResponseReadyEvent;
use Illuminate\Support\Facades\Cache;

class ResponseReadyEventListener
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
    public function handle( ResponseReadyEvent $event)
    {
        Cache::forget( 'ns-core-installed' );
    }
}
