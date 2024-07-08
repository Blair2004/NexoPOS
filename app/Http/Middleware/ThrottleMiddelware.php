<?php

namespace App\Http\Middleware;

use App\Traits\NsMiddlewareArgument;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Symfony\Component\HttpFoundation\Response;

class ThrottleMiddelware extends ThrottleRequests
{
    use NsMiddlewareArgument;

    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle( $request, Closure $next, $maxAttempts = 60, $decayMinutes = 1, $prefix = '' ): Response
    {
        if ( ! in_array( strtolower( app()->environment() ), [ 'local', 'testing', 'development' ] ) ) {
            if ( is_string( $maxAttempts )
                && func_num_args() === 3
                && ! is_null( $limiter = $this->limiter->limiter( $maxAttempts ) ) ) {
                return $this->handleRequestUsingNamedLimiter( $request, $next, $maxAttempts, $limiter );
            }

            return $this->handleRequest(
                $request,
                $next,
                [
                    (object) [
                        'key' => $prefix . $this->resolveRequestSignature( $request ),
                        'maxAttempts' => $this->resolveMaxAttempts( $request, $maxAttempts ),
                        'decaySeconds' => 60 * $decayMinutes,
                        'decayMinutes' => $decayMinutes,
                        'responseCallback' => null,
                    ],
                ]
            );
        }

        return $next( $request );
    }
}
