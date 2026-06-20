<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Models\Permission;
use Illuminate\Support\Facades\Auth;

class PermissionController extends DashboardController
{
    public function getSinglePermission( string $namespace )
    {
        return Permission::where( 'namespace', $namespace )->firstOrFail();
    }

    public function getAllGrandedPermissions()
    {
        return Permission::where( 'status', 'granted' )
            ->where( 'granter_id', Auth::id() )
            ->paginate( 10 );
    }

    public function checkPermissionAccess( string $namespace )
    {
        if ( ns()->allowedTo( $namespace ) ) {
            return response()->json( [
                'message' => 'Permission granted',
            ], 200 );
        }

        return response()->json( [
            'message' => 'Permission denied',
        ], 403 );
    }
}
