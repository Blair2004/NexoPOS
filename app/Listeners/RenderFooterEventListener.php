<?php

namespace App\Listeners;

use App\Events\RenderFooterEvent;
use Carbon\Carbon;

class RenderFooterEventListener
{
    public function handle( RenderFooterEvent $event )
    {
        $lastSent = ns()->option->get('ns_telemetry_last_sent');
        $needsSend = empty($lastSent) || Carbon::parse($lastSent)->diffInHours(now()) >= 24;

        if ($needsSend) {
            $event->output->addView('common.telemetry-script');
        }
    }
}
