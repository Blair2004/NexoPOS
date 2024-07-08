<?php

namespace Tests\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\Sanctum;

trait WithAuthentication
{
    protected function attemptAuthenticate( $user = null, $role = 'admin' )
    {
        $user = $user === null ? $this->attemptGetAnyUserFromRole( $role ) : $user;

        Sanctum::actingAs(
            $user,
            ['*']
        );
    }

    protected function attemptGetAnyUserFromRole( $name = 'admin' )
    {
        return User::whereHas( 'roles', function ( Builder $query ) use ( $name ) {
            $query->where( 'namespace', $name );
        } )->get()->random();
    }
}
