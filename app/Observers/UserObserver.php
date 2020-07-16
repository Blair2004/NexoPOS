<?php
namespace App\Observers;

use App\Models\User;

use Illuminate\Support\Facades\DB;
use App\Models\Permission;
use App\Services\UserOptions;

class UserObserver 
{
    public function retrieved( User $user )
    {
        $user->options          =   new UserOptions( $user->id );
    }
}