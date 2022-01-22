<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use App\Services\UserOptions;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, 
        HasFactory, 
        HasApiTokens;

    protected $table    =   'nexopos_users';
    protected $casts    =   [
        'active'    =>  'boolean'
    ];

    /**
     * While saving model, this will
     * use the timezone defined on the settings
     */
    public function freshTimestamp()
    {
        return ns()->date->getNow();
    }

    /**
     * @var App\Services\UserOptions;
     */
    public $options;

    /** @var */
    public $user_id;

    /**
     * The attributes that are mass assignable.
     *
        * @var array
     */
    protected $fillable = [
        'email', 'password', 'role_id', 'active', 'username'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Permission for a specific user
     * @var array
     */
    protected static $permissions  =   [];

    public function __construct( $attributes = [])
    {
        parent::__construct( $attributes );
    }

    public function attribute()
    {
        return $this->hasOne( UserAttribute::class, 'user_id', 'id' );
    }

    /**
     * Relation with roles
     * @return void
    **/
    public function roles()
    {
        return $this->hasManyThrough( 
            Role::class,
            UserRoleRelation::class,
            'user_id',
            'id',
            'id',
            'role_id'
        );
    }

    /**
     * Relation with permissions
     * @return array
     */
    public static function permissions( $user_id = null )
    {
        /**
         * If user id is not provided
         */
        $user       =   $user_id === null ? User::find( Auth::id() ) : User::find( $user_id );

        $roles_id   =   $user
            ->roles()
            ->get()
            ->map( fn( $role ) => $role->id )
            ->toArray();

        foreach( $roles_id as $role_id ) {
            if ( empty( @self::$permissions[ $role_id ] ) ) {

                $rawPermissions   =   Role::find( $role_id )->permissions;
    
                /**
                 * if the permissions hasn't yet been cached
                 */
                
                // start caching the user permissions
                self::$permissions[ $role_id ]    =   [];
        
                /**
                 * if there is a rawPermission available
                 */
                if ( $rawPermissions->count() ) {
                    foreach ( $rawPermissions as $permission ) {
                        self::$permissions[ $role_id ][]  =   $permission->namespace;
                    }
                }
            }
        }

        return collect( self::$permissions )->filter( function( $permission, $key ) use ( $roles_id ) {
                return in_array( $key, $roles_id );
            })
            ->flatten()
            ->unique()
            ->toArray();
    }

    /**
     * Check if a user can perform an action
     */
    public static function allowedTo( $action, $type = 'all' )
    {
        if ( ! is_array( $action ) ) {
            // check if there is a wildcard on the permission request
            $partials       =   explode( '.', $action );

            if ( $partials[0] == '*' ) {
                /**
                 * Getting all defined permission instead of hard-coding it
                 */
                $permissions    =   collect( self::permissions() )
                    ->filter( function( $value, $key ) use ( $partials ) {
                        return substr( $value, -strlen( $partials[1] ) ) === $partials[1];
                    });

                return self::allowedTo( $permissions->toArray() );
            }

            /**
             * We assume the search is not an array but a string. We can then perform a search
             */
            return in_array( $action, self::permissions() );

        } else {

            /**
             * While looping, if one permission is not granted, exit the loop and return false
             */
            if ( $action ) {
                $hasPassed  =   false;
                foreach ( $action as $_permission ) {
                    if ( ! self::allowedTo( $_permission ) && $type === 'all' ) {
                        return false;
                    } else if ( self::allowedTo( $_permission ) && $type === 'some' ) {
                        $hasPassed  =   true;
                    }
                }    
                return $type === 'all' ? true : $hasPassed;
            }
            return false;
        }
    }

    /**
     * Get authenticated user pseudo
     * @return string
     */
    public function pseudo()
    {
        return Auth::user()->username;
    }

    /**
     * Assign user to a role
     * @param int user id
     * @param role name
     * @return boolean
     */
    public static function setAs( $user, $roleName )
    {
        if ( $role = Role::namespace( $roleName ) ) {
            if ( $user instanceof User ) {
                $combinaison    =   UserRoleRelation::combinaison( $user, $role )->first();

                if ( ! $combinaison instanceof UserRoleRelation ) {
                    $combinaison    =   new UserRoleRelation;
                }

                $combinaison->user_id   =   $user->id;
                $combinaison->role_id   =   $role->id;
                $combinaison->save();
            } else {
                $user   =   User::find( $user );

                if ( $user instanceof User ) {
                    return User::setAs( $user, $roleName );
                }
            }
        }
        return false;
    }

    /**
     * Set object as a role
     * basically assigning role to user
     * @param object user
     * @return User<Model>
     */
    public static function set( $user )
    {
        return $user ? User::define( $user->id ) : false;
    }

    /**
     * Define user id
     * @param int user
     */
    public static function define( $user_id ) 
    {
        $user   =   new User;
        $user->user_id  =   $user_id;
        return $user;
    }

    /**
     * Assign a role
     * @param string role namespace
     * @return void
     */
    public function as( $role )
    {
        return self::setAs( $this->user_id, $role );
    }
    
    /**
     * mutator
     * mutate active field
     * @param string value
     * @return boolean
     */
    public function getActiveAttribute( $value )
    {
        return $value == '1';
    }

    /**
     * Quick access to user options
     */
    public function options( $option, $default = null ) 
    {
        $options    =   new UserOptions( $this->id );
        return $options->get( $option, $default );
    }
}
