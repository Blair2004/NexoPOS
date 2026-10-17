<?php

namespace Tests\Unit\Events;

use App\Classes\Output;
use App\Events\RenderHeaderEvent;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RenderHeaderEventTest extends TestCase
{
    public function test_output_dispatch_forwards_the_route_name_to_the_header_event(): void
    {
        Event::fake( [RenderHeaderEvent::class] );

        Output::dispatch( RenderHeaderEvent::class, 'ns.dashboard.home' );

        Event::assertDispatched(
            RenderHeaderEvent::class,
            fn ( RenderHeaderEvent $event ): bool => $event->routeName === 'ns.dashboard.home'
        );
    }

    public function test_header_event_accepts_an_unnamed_route(): void
    {
        Event::fake( [RenderHeaderEvent::class] );

        Output::dispatch( RenderHeaderEvent::class, null );

        Event::assertDispatched(
            RenderHeaderEvent::class,
            fn ( RenderHeaderEvent $event ): bool => $event->routeName === null
        );
    }
}
