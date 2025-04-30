<?php

namespace App\Listeners;

use App\Events\RenderProfileFooterEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RenderProfileFooterEventListener
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
    public function handle(RenderProfileFooterEvent $event): void
    {
        //
    }
}
