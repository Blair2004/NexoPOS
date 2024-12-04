<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

/**
 * @property int            $total_stores
 * @property string         $description
 * @property bool           $locked
 * @property \Carbon\Carbon $updated_at
 */
class Role extends NsRootModel
{
    use HasFactory;

    protected $table = 'nexopos_roles';

    protected $fillable = [ 'namespace', 'name', 'description', 'reward_system_id', 'minimal_credit_payment' ];

    /**
     * @var string ADMIN main role with all permissions
     */
    const ADMIN = 'admin';

    /**
     * @var string STOREADMIN store manager
     */
    const STOREADMIN = 'nexopos.store.administrator';

    /**
     * @var string STORECASHIER store role with sales capacity
     */
    const STORECASHIER = 'nexopos.store.cashier';

    /**
     * @var string STOREDRIVER store role with purchasing capacity
     */
    const STORECUSTOMER = 'nexopos.store.customer';

    /**
     * @var string USER base role with no or less permissions
     */
    const USER = 'user';

    protected $cats = [
        'locked' => 'boolean',
    ];

    protected $guarded = [ 'id' ];

    /**
     * Relation with users
     **/
    public function users()
    {
        return $this->hasManyThrough(
            User::class,
            UserRoleRelation::class,
            'role_id',
            'id',
            'id',
            'user_id',
        );
    }

    /**
     * Relation with Permissions
     **/
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany( Permission::class, 'nexopos_role_permission' );
    }

    public function scopeWithNamespace( $query, $param )
    {
        return $query->where( 'namespace', $param );
    }

    /**
     * Get Name
     *
     * @param string role name
     * @return Role
     **/
    public static function namespace( $name )
    {
        return self::where( 'namespace', $name )->first();
    }

    /**
     * Filter group matching the array provided as an argument
     *
     * @param Query
     * @param  array $arguments
     * @return Query
     */
    public function scopeIn( $query, $arguments )
    {
        return $query->whereIn( 'namespace', $arguments );
    }

    /**
     * Add permission to an existing role
     *
     * @param array|string Permissions
     * @param bool silent
     */
    public function addPermissions( $permissions, $silent = false )
    {
        if ( is_string( $permissions ) ) {
            $permission = Permission::namespace( $permissions );

            if ( $permission instanceof Permission ) {
                return self::__createRelation( $this, $permission, $silent );
            }

            throw new Exception( sprintf( __( 'Unable to find the permission with the namespace "%s".' ), $permissions ) );
        } elseif ( $permissions instanceof Collection ) {
            /**
             * looping over provided permissions
             * and attempt to create a relation
             */
            $permissions->each( function ( $permissionNamespace ) {
                $this->addPermissions( $permissionNamespace );
            } );
        } elseif ( is_array( $permissions ) ) {
            /**
             * looping over provided permissions
             * and attempt to create a relation
             */
            collect( $permissions )->each( function ( $permissionNamespace ) {
                $this->addPermissions( $permissionNamespace );
            } );
        } elseif ( $permissions instanceof Permission ) {
            return $this->addPermissions( $permissions->namespace, $silent );
        }
    }

    /**
     * create relation between role and permissions
     *
     * @param  Role       $role
     * @param  Permission $permission
     * @param  bool       $silent
     * @return void
     */
    private static function __createRelation( $role, $permission, $silent = true )
    {
        /**
         * If we want it to be silent
         * then we should'nt trigger any error
         * if the $role is not a valid instance.
         */
        if ( ! $role instanceof Role && $silent === false ) {
            return; //
        }

        $rolePermission = RolePermission::where( 'role_id', $role->id )
            ->where( 'permission_id', $permission->id )
            ->first();

        /**
         * if the relation already exists, we'll just skip
         * that and proceed
         */
        if ( ! $rolePermission instanceof RolePermission ) {
            $rolePermission = new RolePermission;
            $rolePermission->permission_id = $permission->id;
            $rolePermission->role_id = $role->id;
            $rolePermission->save();
        }
    }

    /**
     * is used to remove a set of permission
     * attached to the
     *
     * @param array of permissions
     * @return void
     */
    public function removePermissions( $permissionNamespace )
    {
        if ( $permissionNamespace instanceof Collection ) {
            $permissionNamespace->each( fn( $permission ) => $this->removePermissions( $permission instanceof Permission ? $permission->namespace : $permission ) );
        } else {
            $permission = Permission::where( [ 'namespace' => $permissionNamespace ] )
                ->first();

            if ( $permission instanceof Permission ) {
                RolePermission::where( [
                    'role_id' => $this->id,
                    'permission_id' => $permission->id,
                ] )->delete();
            } else {
                throw new Exception( sprintf(
                    __( 'Unable to remove the permissions "%s". It doesn\'t exists.' ),
                    $permissionNamespace
                ) );
            }
        }
    }
}
