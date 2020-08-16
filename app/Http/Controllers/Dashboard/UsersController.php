<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


use Tendoo\Core\Exceptions\CoreException;

use App\Models\ProductCategory;
use Exception;

class UsersController extends DashboardController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function listUsers()
    {
        return $this->view( 'pages.dashboard.crud.table', [
            'title'         =>      __( 'Users List' ),
            'createLink'    =>  url( '/dashboard/users/create' ),
            'description'   =>  __( 'Manage all users available.' ),
            'src'           =>  url( '/api/nexopos/v4/crud/ns.users' ),
        ]);
    }
}

