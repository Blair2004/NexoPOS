<?php

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Auth;
use App\Services\UserOptions;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable, HasApiTokens;

    protected $table    =   'nexopos_users';
    protected $casts    =   [
        'active'    =>  'boolean'
    ];

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

    /**
     * Relation with roles
     * @return void
    **/
    public function role()
    {
        return $this->belongsTo( Role::class );
    }

    /**
     * Relation with permissions
     * @return object
     */
    public static function permissions( $user_id = null )
    {
        /**
         * If user id is not provided
         */
        $user_id = $user_id ?: Auth::user()->role_id;

        if ( empty( @self::$permissions[ $user_id ] ) ) {

            $rawPermissions   =   Role::find( $user_id )->permissions;

            /**
             * if the permissions hasn't yet been cached
             */
            
            // start caching the user permissions
            self::$permissions[ $user_id ]    =   [];
    
            /**
             * if there is a rawPermission available
             */
            if ( $rawPermissions->count() ) {
                foreach ( $rawPermissions as $permission ) {
                    self::$permissions[ $user_id ][]  =   $permission->namespace;
                }
            }
        }

        return self::$permissions[ $user_id ];
    }

    /**
     * Check if a user can perform an action
     */
    public static function allowedTo( $action )
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
                foreach ( $action as $_permission ) {
                    if ( ! self::allowedTo( $_permission ) ) {
                        return false;
                    }
                }    
                return true;
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
    public static function setAs( $id, $roleName )
    {
        if ( $role = Role::namespace( $roleName ) ) {
            /**
             * check if model is already provided
             */
            $user = is_object( $id ) ? $id : self::find( $id );

            $user->role()->associate( $role );
            $user->save();
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
}
