<?php

namespace App\Observers;

use App\Models\User;
use App\Services\UserOptions;

class UserObserver
{
    public function retrieved( User $user )
    {
        $user->options = new UserOptions( $user->id );
    }
}
