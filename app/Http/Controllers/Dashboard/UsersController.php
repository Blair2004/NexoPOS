<?php

/**
 * NexoPOS Controller
 *
 * @since  1.0
 **/

namespace App\Http\Controllers\Dashboard;

use App\Crud\RolesCrud;
use App\Crud\UserCrud;
use App\Exceptions\NotFoundException;
use App\Http\Controllers\DashboardController;
use App\Http\Requests\UpdateWidgetLayoutRequest;
use App\Models\Permission;
use App\Models\PermissionAccess;
use App\Models\Role;
use App\Models\User;
use App\Services\DateService;
use App\Services\GuideService;
use App\Services\UserOptions;
use App\Services\UsersService;
use App\Services\WidgetService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;

class UsersController extends DashboardController
{
    public function __construct(
        protected UsersService $usersService,
        protected DateService $dateService,
        protected WidgetService $widgetService,
        protected GuideService $guideService
    ) {
        // ...
    }

    /**
     * List all users
     *
     * @return View
     */
    public function listUsers()
    {
        return UserCrud::table();
    }

    /**
     * Create a new user
     *
     * @return View
     */
    public function createUser()
    {
        ns()->restrict( [ 'create.users' ] );

        return UserCrud::form();
    }

    /**
     * Edit a user
     *
     * @return View
     */
    public function editUser( User $user )
    {
        ns()->restrict( [ 'update.users' ] );

        if ( $user->id === Auth::id() ) {
            return redirect( ns()->route( 'ns.dashboard.users.profile' ) );
        }

        return UserCrud::form( $user );
    }

    /**
     * Returns a list of users
     *
     * @return array
     */
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
     * @return View
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

    /**
     * Create a new role
     *
     * @return View
     */
    public function createRole( Role $role )
    {
        return RolesCrud::form();
    }

    /**
     * Clone a role
     *
     * @return Role
     *
     * @throws Exception
     */
    public function cloneRole( Role $role )
    {
        ns()->restrict( [ 'create.roles' ] );

        return $this->usersService->cloneRole( $role );
    }

    /**
     * Configure widgets on areas
     */
    public function configureWidgets( UpdateWidgetLayoutRequest $request ): array
    {
        $widgetRegistry = $this->widgetService->getWidgets()->keyBy( 'component-name' );
        $widgets = collect( $request->validated( 'widgets' ) )
            ->map( function ( array $widgetConfig ) use ( $widgetRegistry ): array {
                $widget = $widgetRegistry->get( $widgetConfig['identifier'] );
                $selectedLayout = $widgetConfig['layout'] ?? null;

                return [
                    'component-name' => $widgetConfig['identifier'],
                    'class-name' => $widget->{'class-name'},
                    'layout' => $selectedLayout === $widget->layout['name'] ? null : $selectedLayout,
                ];
            } )
            ->all();

        return $this->usersService->storeWidgetLayout( $widgets, $request->user() );
    }

    /**
     * Create a new API token for the user
     *
     * @return string
     *
     * @throws Exception
     */
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

    /**
     * Get all tokens for the current user
     *
     * @return array
     */
    public function getTokens()
    {
        return $this->usersService->getTokens();
    }

    /**
     * Delete a token by its ID
     *
     * @param  int  $tokenId
     * @return bool
     */
    public function deleteToken( $tokenId )
    {
        return $this->usersService->deleteToken( $tokenId );
    }

    /**
     * Check if the user has a specific permission
     *
     * @return JsonResponse
     */
    public function checkPermission( Request $request )
    {
        return $this->usersService->requestAccess(
            $request->input( 'permission' )
        );
    }

    /**
     * Get the current user
     *
     * @return User
     */
    public function getUser( Request $request )
    {
        $request->user()->load( [
            'attribute',
            'roles',
        ] );

        return $request->user();
    }

    /**
     * Get the permissions of the current user
     *
     * @return array
     */
    public function getUserPermissions( Request $request )
    {
        $user = $request->user();
        $user->load( 'roles.permissions' );

        return $user->roles->flatMap( function ( $role ) {
            return $role->permissions->map( function ( $permission ) {
                return [
                    'namespace' => $permission->namespace,
                    'name' => $permission->name,
                ];
            } );
        } )->unique( 'namespace' )->values()->all();
    }

    public function approveAccess( Request $request, $id )
    {
        $access = PermissionAccess::with( 'perm' )->find( $id );

        /**
         * We'll proceed only if the permission is valid.
         */
        if ( $access instanceof PermissionAccess ) {

            /**
             * let's check if the permission has not yet expired.
             */
            if ( $access->expired_at && now()->greaterThan( $access->expired_at ) ) {
                $access->status = PermissionAccess::EXPIRED;
                $access->save();

                throw new NotFoundException( __( 'The requested permission access has expired.' ) );
            }

            /**
             * The requested access doesn't match the requested permission.
             */
            if ( $access->permission !== $request->input( 'permission' ) ) {
                throw new NotAllowedException( __( 'The requested permission access is not valid.' ) );
            }

            /**
             * The user is not allowed to access this permission.
             */
            if ( ns()->allowedTo( $access->permission ) ) {
                $access->status = PermissionAccess::GRANTED;
                $access->granter_id = Auth::id();
                $access->save();

                return [
                    'status' => 'success',
                    'message' => __( 'The permission access has been granted.' ),
                    'data' => compact( 'access' ),
                ];
            }

            throw new NotFoundException(
                sprintf(
                    __( 'You do not have access to this permission %s.' ),
                    $access->perm->name
                )
            );
        }

        throw new NotFoundException( __( 'The requested permission access is not found.' ) );
    }

    public function markAccessAsUsed( Request $request, PermissionAccess $access )
    {
        /**
         * check if the user has access to this permission.
         */
        if ( $access->requester_id !== Auth::id() ) {
            throw new NotFoundException( __( 'You do not have access to this permission.' ) );
        }

        $access->status = PermissionAccess::USED;
        $access->save();

        return [
            'status' => 'success',
            'message' => __( 'The permission access has been marked as used.' ),
            'data' => compact( 'access' ),
        ];
    }

    public function getAccess( Request $request, $id )
    {
        $access = PermissionAccess::find( $id );

        if ( ! $access instanceof PermissionAccess ) {
            throw new NotFoundException( __( 'The requested permission access is not found.' ) );
        }

        /**
         * Check if the permission has not yet expired.
         */
        if ( $access->expired_at && now()->greaterThan( $access->expired_at ) ) {
            $access->status = PermissionAccess::EXPIRED;
            $access->save();

            throw new NotFoundException( __( 'The requested permission access has expired.' ) );
        }

        /**
         * check if the user has access to this permission.
         */
        if ( $access->requester_id !== Auth::id() ) {
            throw new NotFoundException( __( 'You do not have access to this permission.' ) );
        }

        return $access;
    }

    /**
     * Snooze ads for 24 hours
     */
    public function snoozeAds( Request $request )
    {
        $userOptions = new UserOptions( Auth::id() );
        $userOptions->set( 'snooze_ads_24h', 'yes', now()->addHours( 24 ) );

        return response()->json( [
            'status' => 'success',
            'message' => __( 'Ads have been snoozed for 24 hours.' ),
        ] );
    }

    public function getGuides( Request $request )
    {
        $validated = $request->validate( [
            'route' => 'sometimes|string',
            'path' => 'sometimes|string',
        ] );

        return $this->guideService
            ->initForUser( $request->user() )
            ->pending( $validated['route'], $validated[ 'path' ] );
    }

    public function dismissGuide( Request $request )
    {
        $guideId = $request->input( 'identifier' );

        if ( ! $guideId ) {
            return response()->json( [
                'status' => 'error',
                'message' => __( 'The guide ID is required.' ),
            ], 400 );
        }

        $this->guideService
            ->initForUser( $request->user() )
            ->dismiss( $guideId );

        return response()->json( [
            'status' => 'success',
            'message' => __( 'The guide has been dismissed.' ),
        ] );
    }

    public function completeGuide( Request $request )
    {
        $guideId = $request->input( 'identifier' );

        if ( ! $guideId ) {
            return response()->json( [
                'status' => 'error',
                'message' => __( 'The guide ID is required.' ),
            ], 400 );
        }

        $this->guideService
            ->initForUser( $request->user() )
            ->complete( $guideId );

        return response()->json( [
            'status' => 'success',
            'message' => __( 'The guide has been completed.' ),
        ] );
    }

    public function getCompletedGuides( Request $request )
    {
        $completed = $this->guideService
            ->initForUser( $request->user() )
            ->completed();

        $page = Paginator::resolveCurrentPage() ?: 1;

        $collection = collect( $completed );

        $currentPageItems = $collection->forPage( $page, 10 )->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            10,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
            ]
        );
    }

    public function getDismissedGuides( Request $request )
    {
        $dismissed = $this->guideService
            ->initForUser( $request->user() )
            ->dismissed();

        $page = Paginator::resolveCurrentPage() ?: 1;

        $collection = collect( $dismissed );

        $currentPageItems = $collection->forPage( $page, 10 )->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $collection->count(),
            10,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
            ]
        );
    }

    public function resetGuide( Request $request )
    {
        $validated = $request->validate( [
            'guide_id' => 'required|string',
        ] );

        $this->guideService
            ->initForUser( $request->user() )
            ->reset( $validated['guide_id'] );

        return [
            'status' => 'success',
            'message' => __( 'The guide has been reset.' ),
        ];
    }
}
