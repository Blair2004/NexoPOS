<?php

namespace App\Listeners;

use App\Events\UserRoleRelationAfterCreatedEvent;
use App\Models\Driver;
use App\Models\Role;
use App\Services\DriverService;

class UserRoleRelationAfterCreatedEventListener
{
    public function __construct(
        public DriverService $driverService
    ) {
        //
    }

    /**
     * Handle the event.
     *
     * @param  UserRoleRelationAfterCreatedEvent  $event
     * @return void
     */
    public function handle( UserRoleRelationAfterCreatedEvent $event)
    {
        $event->userRoleRelation->load( 'user' );

        if ( $event->userRoleRelation->user->hasRoles([ Role::DRIVER ]) ) {
            // when a driver is created, his status
            // is set to offline by default

            $this->driverService->changeStatus(
                driver: Driver::find( $event->userRoleRelation->user_id ),
                status: Driver::STATUS_OFFLINE
            );
        }
    }
}