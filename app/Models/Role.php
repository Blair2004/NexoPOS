<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;

use App\Models\RolePermission;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends NsRootModel
{
    use HasFactory;
    protected $table    =   'nexopos_roles';

    /**
     * @var string ADMIN main role with all permissions
     */
    const ADMIN         =   'admin';

    /**
     * @var string STOREADMIN store manager
     */
    const STOREADMIN    =   'nexopos.store.administrator';

    /**
     * @var string STORECASHIER store role with sales capacity
     */
    const STORECASHIER  =   'nexopos.store.cashier';

    /**
     * @var string USER base role with no or less permissions
     */
    const USER          =   'user';

    /**
     * Default dashboard identifier.
     * Store dashboard
     */
    const DASHID_STORE      =   'store';

    /**
     * Store cashier dashboard.
     */
    const DASHID_CASHIER    =   'cashier';

    /**
     * Default dashboard for other users.
     */
    const DASHID_DEFAULT    =   'default';

    protected $cats     =   [
        'locked'        =>  'boolean'
    ];

    protected $guarded     =   [ 'id' ];

    /**
     * Relation with users
    **/
    public function users()
    {
        return $this->hasMany( User::class );
    }
    
    /**
     * Relation with users
     * @return void
     * @deprecated
    **/
    public function user()
    {
        return $this->hasMany( User::class );
    }

    /**
     * Relation with Permissions
     * @return void
    **/
    public function permissions()
    {
        return $this->belongsToMany( Permission::class, 'nexopos_role_permission' );
    }

    public function scopeWithNamespace( $query, $param ) {
        return $query->where( 'namespace', $param );
    }

    /**
     * Get Name
     * @param string role name
     * @return Role
    **/
    public static function namespace( $name )
    {
        return self::where( 'namespace', $name )->first();
    }

    /**
     * @param string namespace
     * @deprecated
     * @return Role
     */
    public static function withNamespace( $name )
    {
        return self::where( 'namespace', $name );
    }

    /**
     * Filter group matching the array provided as an argument
     * @param Query
     * @param array $arguments
     * @return Query
     */
    public function scopeIn( $query, $arguments )
    {
        return $query->whereIn( 'namespace', $arguments );
    }

    /**
     * Add permission to an existing role
     * @param array|string Permissions
     * @param boolean silent
     */
    public function addPermissions( $permissions, $silent = false )
    {
        if ( is_string( $permissions ) ) {
            $permission     =   Permission::namespace( $permissions );

            if ( $permission instanceof Permission ) {
                return self::__createRelation( $this, $permission, $silent );
            }

            throw new Exception( sprintf( __( 'Unable to find the permission with the namespace "%s".'), $permissions ) );

        } else if ( $permissions instanceof Collection ) {
            /**
             * looping over provided permissions
             * and attempt to create a relation
             */
            $permissions->each( function( $permissionNamespace ) {
                $this->addPermissions( $permissionNamespace );
            });
        } else if ( is_array( $permissions ) ) {
            /**
             * looping over provided permissions
             * and attempt to create a relation
             */
            collect( $permissions )->each( function( $permissionNamespace ) {
                $this->addPermissions( $permissionNamespace );
            });
        } else if ( $permissions instanceof Permission ) {
            return $this->addPermissions( $permissions->namespace, $silent );
        }
    }

    /**
     * create relation between role and permissions
     * @param Role $role
     * @param Permission $permission
     * @param boolean $silent
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

        $rolePermission     =   RolePermission::where( 'role_id', $role->id )
            ->where( 'permission_id', $permission->id )
            ->first();

        /**
         * if the relation already exists, we'll just skip 
         * that and proceed
         */
        if ( ! $rolePermission instanceof RolePermission ) {
            $rolePermission                     =    new RolePermission;
            $rolePermission->permission_id      =   $permission->id;
            $rolePermission->role_id            =   $role->id;
            $rolePermission->save();
        }
    }

    /**
     * is used to remove a set of permission
     * attached to the 
     * @param array of permissions
     * @return void
     */
    public function removePermissions( $permissionNamespace )
    {
        if ( $permissionNamespace instanceof Collection ) {
            $permissionNamespace->each( fn( $permission ) => $this->removePermissions( $permission instanceof Permission ? $permission->namespace : $permission ) );
        } else {
            $permission     =   Permission::where([ 'namespace' => $permissionNamespace ])
                ->first();

            if ( $permission instanceof Permission ) {
                RolePermission::where([ 
                    'role_id' => $this->id, 
                    'permission_id' => $permission->id, 
                ])->delete();
            } else {
                throw new Exception( sprintf( 
                    __( 'Unable to remove the permissions "%s". It doesn\'t exists.' ),
                    $permissionNamespace
                ) );
            }
        }
    }
}
