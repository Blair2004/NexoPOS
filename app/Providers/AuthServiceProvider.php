<?php

namespace App\Providers;

use App\Models\PersonalAccessToken;
use App\Services\CoreService;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Sanctum\Sanctum;

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
    public function boot( CoreService $coreService )
    {
        $coreService->registerGatePermissions();

        Sanctum::usePersonalAccessTokenModel( PersonalAccessToken::class );
    }
}
