<?php

namespace Tests\Traits;

use App\Models\Role;
use Laravel\Sanctum\Sanctum;

trait WithAuthentication
{
    protected function attemptAuthenticate( $user = null )
    {
        $user = $user === null ? $this->attemptGetAnyUserFromRole() : $user;

        Sanctum::actingAs(
            $user,
            ['*']
        );
    }

    protected function attemptGetAnyUserFromRole( $name = 'admin' )
    {
        return Role::namespace( 'admin' )->users->random();
    }
}
