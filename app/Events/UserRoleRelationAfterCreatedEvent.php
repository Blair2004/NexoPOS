<?php

namespace App\Events;

use App\Models\UserRoleRelation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRoleRelationAfterCreatedEvent
{
    use Dispatchable, SerializesModels;

    public $userRoleRelation;

    public function __construct( UserRoleRelation $userRoleRelation )
    {
        $this->userRoleRelation = $userRoleRelation;
    }
}
