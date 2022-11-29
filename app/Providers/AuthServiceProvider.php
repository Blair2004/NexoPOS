<?php

namespace App\Providers;

use App\Models\Permission;
use App\Models\User;
use App\Services\Helper;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // ...
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        /**
         * We'll define gate by using
         * all available permissions.
         */
        if ( Helper::installed() ) {
            Permission::get()->each( function( $permission ) {
                Gate::define( $permission->namespace, function( User $user ) use ( $permission ) {
                    $permissions    =   Cache::remember( 'ns-all-permissions-' . $user->id, 3600, function() use ( $user ) {
                        return $user->roles()
                            ->with( 'permissions' )
                            ->get()
                            ->map( fn( $role ) => $role->permissions->map( fn( $permission ) => $permission->namespace ) )
                            ->flatten();
                    })->toArray();
    
                    return in_array( $permission->namespace, $permissions );
                });
            });
        }
    }
}
