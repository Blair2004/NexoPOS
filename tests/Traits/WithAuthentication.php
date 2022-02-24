<?php
namespace Tests\Traits;

use App\Models\Role;
use Laravel\Sanctum\Sanctum;

trait WithAuthentication
{
    protected function attemptAuthenticate()
    {
        $user   =   Role::namespace( 'admin' )->users->first();

        Sanctum::actingAs(
            $user,
            ['*']
        );
    }
}