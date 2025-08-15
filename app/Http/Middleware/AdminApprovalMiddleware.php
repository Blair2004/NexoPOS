<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class AdminApprovalMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle( Request $request, Closure $next, $permission ): Response
    {
        $approvalSessionKey = 'approval_' . $permission;
        $approvedUrl = Session::get( $approvalSessionKey );

        // Check if approval exists and matches this request URL
        if ( $approvedUrl && $approvedUrl === $request->fullUrl() ) {
            return $next( $request );
        }

        // Block request and send required info
        return response()->json( [
            'message' => __( 'You need to get approval for this action.' ),
            'approval_url' => route( 'approval.verify', ['permission' => $permission] ),
            'url' => $request->fullUrl(),
        ], 403 );
    }
}
