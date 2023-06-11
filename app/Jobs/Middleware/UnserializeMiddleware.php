<?php

namespace App\Jobs\Middleware;

use App\Events\JobAfterUnserializeEvent;

class UnserializeMiddleware
{
    public function handle( $job, $next )
    {
        JobAfterUnserializeEvent::dispatch( $job );

        return $next( $job );
    }
}
