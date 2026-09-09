<?php

namespace Modules\NsOxen\Listeners;

use App\Events\RenderHeaderEvent;

class RenderOxenHeader
{
    public function handle( RenderHeaderEvent $event ): void
    {
        if ( auth()->check() && str_starts_with( (string) $event->routeName, 'ns.dashboard' ) && ! str_contains( (string) $event->routeName, 'pos' ) && ( ns()->allowedTo( 'ns.oxen.use' ) || ns()->allowedTo( 'ns.oxen.manage' ) ) ) {
            $event->output->addView( 'NsOxen::launcher-header' );
        }
    }
}
