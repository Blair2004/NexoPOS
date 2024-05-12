<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Crud\RolesCrud;
use App\Crud\UserCrud;
use App\Http\Controllers\DashboardController;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\DateService;
use App\Services\UsersService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

class UsersController extends DashboardController
{
    public function __construct(
        protected UsersService $usersService,
        protected DateService $dateService
    ) {
        // ...
    }

    public function listUsers()
    {
        return UserCrud::table();
    }

    public function createUser()
    {
        ns()->restrict( [ 'create.users' ] );

        return UserCrud::form();
    }

    public function editUser( User $user )
    {
        ns()->restrict( [ 'update.users' ] );

        if ( $user->id === Auth::id() ) {
            return redirect( ns()->route( 'ns.dashboard.users.profile' ) );
        }

        return UserCrud::form( $user );
    }

    public function getUsers( User $user )
    {
        ns()->restrict( [ 'read.users' ] );

        return User::get( [ 'username', 'id', 'email' ] );
    }

    /**
     * displays the permission manager UI
     *
     * @return View
     */
    public function permissionManager()
    {
        /**
         * force permissions check
         */
        ns()->restrict( [ 'update.roles' ] );

        return View::make( 'pages.dashboard.users.permission-manager', [
            'title' => __( 'Permission Manager' ),
            'description' => __( 'Manage all permissions and roles' ),
        ] );
    }

    /**
     * displays the user profile
     *
     * @return view
     */
    public function getProfile()
    {
        ns()->restrict( [ 'manage.profile' ] );

        return View::make( 'pages.dashboard.users.profile', [
            'title' => __( 'My Profile' ),
            'description' => __( 'Change your personal settings' ),
            'src' => url( '/api/forms/ns.user-profile' ),
            'submitUrl' => url( '/api/users/profile' ),
        ] );
    }

    /**
     * returns a list of existing roles
     *
     * @return array roles with permissions
     */
    public function getRoles()
    {
        return Role::with( 'permissions' )->get();
    }

    /**
     * Returns a list of permissions
     *
     * @return array permissions
     */
    public function getPermissions()
    {
        return Permission::get();
    }

    /**
     * update roles permissions
     *
     * @return Json
     */
    public function updateRole( Request $request )
    {
        ns()->restrict( [ 'update.roles' ] );

        $roles = $request->all();

        foreach ( $roles as $roleNamespace => $permissions ) {
            $role = Role::namespace( $roleNamespace );

            if ( $role instanceof Role ) {
                $removedPermissions = collect( $permissions )->filter( fn( $permission ) => ! $permission );
                $grantedPermissions = collect( $permissions )->filter( fn( $permission ) => $permission );

                $role->removePermissions( $removedPermissions->keys() );
                $role->addPermissions( $grantedPermissions->keys() );
            }
        }

        return [
            'status' => 'success',
            'message' => __( 'The permissions has been updated.' ),
        ];
    }

    /**
     * List all available roles
     *
     * @return View
     */
    public function rolesList()
    {
        ns()->restrict( [ 'read.roles' ] );

        return RolesCrud::table();
    }

    /**
     * List all available roles
     *
     * @return View
     */
    public function editRole( Role $role )
    {
        ns()->restrict( [ 'update.roles' ] );

        return RolesCrud::form( $role );
    }

    public function createRole( Role $role )
    {
        return RolesCrud::form();
    }

    public function cloneRole( Role $role )
    {
        ns()->restrict( [ 'create.roles' ] );

        return $this->usersService->cloneRole( $role );
    }

    public function configureWidgets( Request $request )
    {
        return $this->usersService->storeWidgetsOnAreas( $request->only( [ 'column' ] ) );
    }

    public function createToken( Request $request )
    {
        $validation = Validator::make( $request->all(), [
            'name' => 'required',
        ] );

        if ( ! $validation->passes() ) {
            throw new Exception( __( 'The provided data aren\'t valid' ) );
        }

        return $this->usersService->createToken( $request->input( 'name' ) );
    }

    public function getTokens()
    {
        return $this->usersService->getTokens();
    }

    public function deleteToken( $tokenId )
    {
        return $this->usersService->deleteToken( $tokenId );
    }

    public function checkPermission( Request $request )
    {
        $result = $this->usersService->checkPermission( $request->input( 'permission' ) );

        if ( $result ) {
            return response()->json( [
                'status' => 'success',
                'message' => __( 'The permission is granted' ),
            ] );
        } else {
            return response()->json( [
                'status' => 'error',
                'message' => __( 'The permission is denied' ),
            ], 403 );
        }
    }
}
