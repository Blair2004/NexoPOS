<?php

namespace App\Http;

use App\Http\Middleware\CheckApplicationHealthMiddleware;
use App\Http\Middleware\CheckMigrationStatus;
use App\Http\Middleware\KillSessionIfNotInstalledMiddleware;
use App\Http\Middleware\LoadLangMiddleware;
use App\Http\Middleware\ProtectRoutePermissionMiddleware;
use App\Http\Middleware\ProtectRouteRoleMiddleware;
use App\Http\Middleware\SanitizePostFieldsMiddleware;
use App\Http\Middleware\ThrottleMiddelware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \App\Http\Middleware\CheckForMaintenanceMode::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            KillSessionIfNotInstalledMiddleware::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            LoadLangMiddleware::class,
        ],

        'api' => [
            EnsureFrontendRequestsAreStateful::class,
            LoadLangMiddleware::class,
            ThrottleMiddelware::class . ':80,1',
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $middlewareAliases = [
        'ns.not-installed' => \App\Http\Middleware\NotInstalledStateMiddleware::class,
        'ns.installed' => \App\Http\Middleware\InstalledStateMiddleware::class,
        'ns.clear-cache' => \App\Http\Middleware\ClearRequestCacheMiddleware::class,
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'bindings' => \Illuminate\Routing\Middleware\SubstituteBindings::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'ns.check-migrations' => CheckMigrationStatus::class,
        'ns.check-application-health' => CheckApplicationHealthMiddleware::class,
        'ns.restrict' => ProtectRoutePermissionMiddleware::class,
        'ns.restrict-role' => ProtectRouteRoleMiddleware::class,
        'ns.sanitize-inputs' => SanitizePostFieldsMiddleware::class,
    ];
}
