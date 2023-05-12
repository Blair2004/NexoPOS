<?php

namespace App\Http\Middleware;

use App\Services\DoctorService;
use Closure;
use Illuminate\Http\Request;

class ForceSetSessionDomainMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /**
         * @var DoctorService
         */
        $doctorService = app()->make( DoctorService::class );
        $doctorService->fixDomains();

        return $next($request);
    }
}
