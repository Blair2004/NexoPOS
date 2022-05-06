<?php
namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;

use App\Services\UserOptions;
use App\Services\DateService;

use App\Classes\Hook;

use App\Mail\ActivateAccountMail;

use App\Models\UserAttribute;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class Users
{
    private $roles  =   [];
    private $users  =   [];

    public function __construct(
        $roles,
        $user,
        Permission $permission
    )
    {
        $this->roles        =   $roles;
        $this->user         =   $user;
        $this->permission   =   $permission;
    }

    /**
     * get all user from a specific group
     * @param string
     * @return array of users
     */
    public function all( $namespace = null ) 
    {
        if ( $namespace != null ) {
            return @$this->roles[ $namespace ][ 'users' ];
        } else {
            return $this->users;
        }
    }

    /**
     * Activate account using a 
     * code and the user id
     * @param string coe
     * @param int user id
     * @return AsyncResponse
     */
    public function activateAccount( $code, $user_id )
    {
        $user               =   User::find( $user_id );
        $date               =   app()->make( DateService::class );

        if ( ! $user instanceof User ) {
            throw new Exception( __( 'The activation process has failed.' ) );
        }

        $userOptions        =   new UserOptions( $user->id );
        $activationCode     =   $userOptions->get( 'activation-code' );
        $expiration         =   $userOptions->get( 'activation-expiration' );

        if ( $activationCode !== $code ) {
            throw new Exception(
                __( 'Unable to activate the account. The activation token is wrong.' )
            );
        }

        if ( $date->greaterThan( Carbon::parse( $expiration ) ) ) {
            throw new Exception(
                __( 'Unable to activate the account. The activation token has expired.' )
            );
        }

        $user->active     =   true;
        $user->save();

        /**
         * we might need to send some
         * email ?
         */

        Hook::action( 'user.activated', $user );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The account has been successfully activated.' )
        ];
    }

    /**
     * Check if a user belongs to a group
     * @param mixed group of user
     * @return boolean
     */
    public function is( $group_name ) 
    {
        $roles      =   Auth::user()
            ->roles
            ->map( fn( $role ) => $role->namespace );

        if ( is_array( $group_name ) ) {
            return $roles
                ->filter( fn( $roleNamespace ) => in_array( $roleNamespace, $group_name ) )
                ->count() > 0;
        } else {
            return in_array( $group_name, $roles->toArray() );
        }
    }

    /**
     * Clone a role assigning same permissions
     * @param Role $role
     * @return array
     */
    public function cloneRole( Role $role )
    {
        $newRole    =   $role->toArray();

        unset( $newRole[ 'id' ] );
        unset( $newRole[ 'created_at' ] );
        unset( $newRole[ 'updated_at' ] );

        /**
         * We would however like
         * to provide a unique name and namespace
         */
        $name       =   sprintf( 
            __( 'Clone of "%s"' ),
            $newRole[ 'name' ]
        );

        $namespace      =   Str::slug( $name );

        $newRole[ 'name' ]      =   $name;
        $newRole[ 'namespace' ] =   $namespace;
        $newRole[ 'locked' ]    =   0; // shouldn't be locked by default.

        /**
         * @var Role
         */
        $newRole    =   Role::create( $newRole );
        $newRole->addPermissions( $role->permissions );

        return [
            'status'    =>  'success',
            'message'   =>  __( 'The role has been cloned.' )
        ];
    }

    /**
     * Will create the user attribute
     * for the provided user if that doesn't
     * exist yet.
     * @param User $user
     * @return void
     */
    public function createAttribute( User $user ): void
    {
        if ( ! $user->attribute instanceof UserAttribute ) {
            $userAttribute              =   new UserAttribute;
            $userAttribute->user_id     =   $user->id;
            $userAttribute->language    =   ns()->option->get( 'ns_store_language' );
            $userAttribute->save();
        }
    }
}