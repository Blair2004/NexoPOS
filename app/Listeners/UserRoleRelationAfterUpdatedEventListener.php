<?php

namespace App\Listeners;

use App\Events\UserRoleRelationAfterUpdatedEvent;

class UserRoleRelationAfterUpdatedEventListener
{
    public function handle( UserRoleRelationAfterUpdatedEvent $event )
    {
        // Add logic to handle the event when a UserRoleRelation is updated
    }
}
