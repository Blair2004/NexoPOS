<?php
namespace App\Listeners;

use App\Events\RenderFooterEvent;

class RenderFooterEventListener
{
    public function handle(RenderFooterEvent $event)
    {
        if ($event->routeName === 'ns.dashboard.home') {
            $event->output->addView('widgets.drivers-widget');
        }
    }
}
