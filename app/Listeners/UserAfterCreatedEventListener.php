<?php

namespace App\Listeners;

use App\Events\UserAfterCreatedEvent;
use App\Models\Driver;
use App\Models\Role;
use App\Services\DriverService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UserAfterCreatedEventListener
{
    /**
     * Create the event listener.
     */
    public function __construct( public DriverService $driverService )
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle( UserAfterCreatedEvent $event ): void
    {
        if ( $event->user->hasRoles([ Role::DRIVER ]) ) {
            // when a driver is created, his status
            // is set to offline by default

            $this->driverService->changeStatus(
                driver: $event->user,
                status: Driver::STATUS_OFFLINE
            );
        }
    }
}
