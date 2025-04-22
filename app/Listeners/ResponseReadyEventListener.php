<?php

namespace App\Listeners;

use App\Classes\Cache;
use App\Events\ResponseReadyEvent;
use Illuminate\Support\Facades\Auth;

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
     * @param  object $event
     * @return void
     */
    public function handle( ResponseReadyEvent $event )
    {
        Cache::forget( 'ns-core-installed' );

        /**
         * if the user is authenticated
         * we'll clear all cached permissions
         */
        if ( Auth::check() ) {
            Cache::forget( 'ns-all-permissions-' . Auth::id() );
        }
    }
}
