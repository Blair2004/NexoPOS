<?php

namespace App\Http\Middleware;

use App\Services\DoctorService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Jackiedo\DotenvEditor\Facades\DotenvEditor;

class ForceSetSessionDomainMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        /**
         * @var DoctorService
         */
        $doctorService  =   app()->make( DoctorService::class );
        $doctorService->fixDomains();

        return $next($request);
    }
}
