<?php
/**
 * @var \Illuminate\Foundation\Configuration\Middleware $middleware
 */

/**
 * We'll list here all aliased middleware.
 */
$middleware->alias( [
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
    'ns.check-migrations' => \App\Http\Middleware\CheckMigrationStatus::class,
    'ns.check-application-health' => \App\Http\Middleware\CheckApplicationHealthMiddleware::class,
    'ns.sanitize-inputs' => \App\Http\Middleware\SanitizePostFieldsMiddleware::class,
] );

/**
 * We'll now register middlewaregroups
 */
$middleware->group( 'web', [
    \App\Http\Middleware\EncryptCookies::class,
    \App\Http\Middleware\KillSessionIfNotInstalledMiddleware::class,
    \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
    \Illuminate\Session\Middleware\StartSession::class,
    \Illuminate\Session\Middleware\AuthenticateSession::class,
    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
    \App\Http\Middleware\VerifyCsrfToken::class,
    \App\Http\Middleware\LoadLangMiddleware::class,
] );

/**
 * We'll now register the api middleware group
 */
$middleware->group( 'api', [
    \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    \App\Http\Middleware\LoadLangMiddleware::class,
    \App\Http\Middleware\ThrottleMiddelware::class . ':80,1',
] );
