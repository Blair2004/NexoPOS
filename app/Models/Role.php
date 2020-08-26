<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;

use App\Models\RolePermission;
use Exception;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $table    =   'nexopos_roles';

    protected $cats     =   [
        'locked'        =>  'boolean'
    ];

    /**
     * Relation with users
     * @return void
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

    /**
     * Get Name
     * @param string role name
     * @return model
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
     * Add permission to an existing role
     * @param array|string Permissions
     * @param boolean silent
     */
    public function addPermissions( $permissions, $silent = false )
    {
        if ( is_string( $permissions ) ) {
            $permission     =   Permission::namespace( $permissions )->first();

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
        $rolePermission     =   RolePermission::where( 'role_id', $role->id )
            ->where( 'permission_id', $permission->id )
            ->first();

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
            $permissionNamespace->each( fn( $permission ) => $this->removePermissions( $permission ) );
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
