<?php

namespace App\Observers;

use App\Models\RewardSystemRule;

class RewardSystemObserver
{
    public function deleting( $reward )
    {
        RewardSystemRule::attachedTo( $reward->id )->delete();
    }
}
