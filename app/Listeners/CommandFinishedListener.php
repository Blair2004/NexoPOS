<?php

namespace App\Listeners;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Support\Facades\Cache;

class CommandFinishedListener
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
    public function handle( CommandFinished $event ): void
    {
        /**
         * because if we're running the reset from the command line
         * we might not have this cache entry deleted. We'll delete it when the reset is done.
         */
        Cache::delete( 'ns-core-installed' );
    }
}
