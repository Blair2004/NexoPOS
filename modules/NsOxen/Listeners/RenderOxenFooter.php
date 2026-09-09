<?php

namespace Modules\NsOxen\Listeners;

use App\Events\RenderFooterEvent;

class RenderOxenFooter
{
    public function handle( RenderFooterEvent $event ): void
    {
        if ( auth()->check() && str_starts_with( (string) $event->routeName, 'ns.dashboard' ) && ! str_contains( (string) $event->routeName, 'pos' ) && ns()->allowedTo( 'ns.oxen.use' ) ) {
            $event->output->addView( 'NsOxen::launcher-footer' );
        }
    }
}
