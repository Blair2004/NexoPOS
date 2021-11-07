<?php
namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;

use App\Services\UserOptions;
use App\Services\DateService;

use App\Classes\Hook;

use App\Mail\ActivateAccountMail;

use App\Exceptions\NotFoundException;
use App\Exceptions\AccessDeniedException;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Exception;

class Users
{
    private $roles  =   [];
    private $users  =   [];

    public function __construct(
        Role $role,
        User $user,
        Permission $permission
    )
    {
        $this->role         =   $role;
        $this->user         =   $user;
        $this->permission   =   $permission;

        // $this->buildRoles();
        // $this->buildUsers();
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
     * BuildRoles
     * @deprecated
     * @return void
     */
    public function buildRoles()
    {
        $roles  =   $this->role->all();

        foreach( $roles as $role ) {
            $this->roles[ $role->namespace ][ 'details' ]    =   $role->toArray();
        }
    }

    /**
     * Build Users
     * @deprecated
     * @return void
     */
    public function buildUsers()
    {
        $users  =   $this->user->all();
        
        foreach( $users as $user ) {

            /***
             * if the role is not cached
             */
            if ( @$this->roles[ $user->role->namespace ][ 'users' ] == null ) {
                $this->roles[ $user->role->namespace ][ 'users' ]     =   [];
            }

            $this->roles[ $user->role->namespace ][ 'users' ][]   =   $user;
            $this->users[]      =   $user;
        }
    }

    /**
     * Send Activation Email
     * @param user id
     */
    public function sendActivationEmail( User $user )
    {
        /**
         * Send user activation code
         */
        $date               =   app()->make( DateService::class );
        $activationCode     =   Str::random( 10 ) . $user->id;
        $userOptions        =   new UserOptions( $user->id );
        $userOptions->set( 'activation-code', $activationCode );
        $userOptions->set( 'activation-expiration', $date->copy()->addDays(2)->toDateTimeString() ); // activation code expires in 2 days

        /**
         * @todo
         * if it shouldn't activate the user, we might send an email
         * for letting him know his account has been created
         */
        Mail::to( $user->email )
            ->queue( new ActivateAccountMail( url( 
                sprintf( '/tendoo/auth/activate?code=%s&user_id=%s', $activationCode, $user->id ) 
            ), $user ) );

        Hook::action( 'auth.send-activation', $user );
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
        if ( is_array( $group_name ) ) {
            return in_array( Auth::user()->role->namespace, $group_name );
        } else {
            return Auth::user()->role->namespace === $group_name;
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
}