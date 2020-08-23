<?php

/**
 * NexoPOS Controller
 * @since  1.0
**/

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\DashboardController;
use App\Http\Requests\UserProfileRequest;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


use Tendoo\Core\Exceptions\CoreException;

use App\Models\ProductCategory;
use App\Models\User;
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

    public function createUser()
    {
        return $this->view( 'pages.dashboard.crud.form', [
            'title'         =>  __( 'Create User' ),
            'returnLink'    =>  url( '/dashboard/users' ),
            'submitUrl'     =>  url( '/api/nexopos/v4/crud/ns.users' ),
            'description'   =>  __( 'Add a new user to the system.' ),
            'src'           =>  url( '/api/nexopos/v4/crud/ns.users/form-config' ),
        ]);
    }

    public function editUser( User $user )
    {
        if ( $user->id === Auth::id() ) {
            return redirect( route( 'dashboard.users.profile' ) );
        }

        /**
         * @temp
         */
        if ( Auth::user()->role->namespace !== 'admin' ) {
            throw new Exception( __( 'Access Denied' ) );
        }
        
        return $this->view( 'pages.dashboard.crud.form', [
            'title'         =>  __( 'Edit User' ),
            'returnLink'    =>  url( '/dashboard/users' ),
            'submitUrl'     =>  url( '/api/nexopos/v4/crud/ns.users/' . $user->id ),
            'submitMethod'  =>  'PUT',
            'description'   =>  __( 'Update an existing user.' ),
            'src'           =>  url( '/api/nexopos/v4/crud/ns.users/form-config/' . $user->id ),
        ]);
    }

    /**
     * displays the user profile
     * @return view
     */
    public function getProfile()
    {
        return $this->view( 'pages.dashboard.users.profile', [
            'title'         =>  __( 'My Profile' ),
            'description'   =>  __( 'Change your personal settings' ),
            'src'           =>  url( '/api/nexopos/v4/forms/ns.user-profile' ),
            'submitUrl'     =>  url( '/api/nexopos/v4/users/profile')
        ]);
    }

    public function updateProfile( UserProfileRequest $request )
    {
        
    }
}

