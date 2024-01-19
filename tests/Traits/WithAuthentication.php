<?php

namespace Tests\Traits;

use App\Models\Role;
use Laravel\Sanctum\Sanctum;

trait WithAuthentication
{
    protected function attemptAuthenticate($user = null, $role = 'admin')
    {
        $user = $user === null ? $this->attemptGetAnyUserFromRole($role) : $user;

        Sanctum::actingAs(
            $user,
            ['*']
        );
    }

    protected function attemptGetAnyUserFromRole($name = 'admin')
    {
        return Role::namespace($name)->users->random();
    }
}
