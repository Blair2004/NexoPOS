<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Models\Permission;

class PermissionController extends DashboardController
{
    public function getSinglePermission( string $namespace )
    {
        return Permission::where( 'namespace', $namespace )->first();
    }
}
