<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Permission;

use App\Models\RolePermission;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{

    private static $cachedPermissions   =   [];
    protected $table    =   'nexopos_roles';

    
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

    public static function withNamespace( $name )
    {
        return self::where( 'namespace', $name );
    }

    /**
     * Permission
     * @param string role name
     * @param array permission array
     * @deprecated
    **/
    public static function AddPermissions( $role_name, $permissions ) 
    {
        $role       =   self::namespace( $role_name );
        
        if( $role ) {
            
            $relations          =   [];

            foreach( ( array ) $permissions as $permission ) {
                $perm       =   explode( '.', $permission );
               

                if( $perm[0] == 'crud' ) {
                    foreach( [ 'create', 'read', 'update', 'delete' ] as $prefix ) {

                        /**
                         * Caching permission, to avoid more request during the installation
                         */
                        if ( @self::$cachedPermissions[ $prefix . '.' . $perm[1] ] == null ) {
                            self::$cachedPermissions[ $prefix . '.' . $perm[1] ]   =   Permission::where( 'namespace', $prefix . '.' . $perm[1] )->first();
                        }

                        
                        $getPerm =  self::$cachedPermissions[ $prefix . '.' . $perm[1] ];

                        $relations[]    =   [
                            'role_id'           =>  $role->id,
                            'permission_id'     =>  $getPerm->id
                        ];
                    }
                } else {
                    if ( @self::$cachedPermissions[ $permission ] == null ) {
                        self::$cachedPermissions[ $permission ]   =   Permission::where( 'namespace', $permission )->first();
                    }

                    $getPerm                =   self::$cachedPermissions[ $permission ];

                    $relations[]    =   [
                        'role_id'           =>  $role->id,
                        'permission_id'     =>  $getPerm->id
                    ];
                }
            }

            /**
             * if the relation array is set, then we can insert all
             */
            if ( $relations ) {
                DB::table( 'nexopos_role_permission' )->insert( $relations );
            }
            return true;
        }

        return false;
    }

    public static function addPermission( $role_name, $permissions, $silent = true )
    {
        $role   =   self::namespace( $role_name );

        if ( $role instanceof Role ) {
            if ( is_string( $permissions ) ) {
                $permission     =   Permission::namespace( $permissions )->first();

                if ( $permission instanceof Permission ) {
                    self::__createRelation( $role, $permission, $silent );
                }
            } else if ( is_array( $permissions ) ) {
                $relations   =   [];
                foreach( $permissions as $permission ) {
                    $permission     =   Permission::namespace( $permissions )->first();

                    if ( $permission instanceof Permission ) {
                        self::__createRelation( $role, $permission, $silent );
                    }
                }
            }
        }
    }

    private static function __createRelation( $role, $permission, $silent )
    {
        $exists     =   DB::table( 'nexopos_role_permission' )
            ->where( 'role_id', $role->id )
            ->where( 'permission_id', $permission->id )
            ->first();

        if ( empty( $exists ) ) {
            DB::table( 'nexopos_role_permission' )->insert([
                'role_id'           =>  $role->id,
                'permission_id'     =>  $permission->id
            ]);
        } else if ( $silent === false ) {
            throw new CoreException([
                'status'    =>  'failed',
                'message'   =>  sprintf(
                    __( 'A relation already exist because role "%s" and permission "%s".' ),
                    $role->name,
                    $permission->name
                )
            ]);
        }
    }

    /**
     * add permissions to 
     * an role model
     * @param array|Collection string permissions
     */
    public function scopeGrantPermissions( $query, $permissions )
    {
        if ( $permissions instanceof Collection ) {
            $roleNamespace      =   $this->namespace;
            $permissions->map( function( $permission ) use ( $roleNamespace ) {
                self::AddPermissions( $roleNamespace, $permission->namespace );
            });
        } else {
            if ( ! is_array( $permissions ) ) {
                return self::AddPermissions( $this->namespace, [ $permissions ]);
            }
            return self::AddPermissions( $this->namespace, $permissions );
        }
    }


    /**
     * is used to remove a set of permission
     * attached to the 
     * @param Query
     * @param array of permissions
     * @return void
     */
    public function scopeRemovePermissions( $query, $permissions )
    {
        $query
            ->get()
            ->each( function( $role ) use ( $permissions ) {
                collect( $permissions )->each( function( $perm_namespace ) use ( $role ) {
                    $permission     =   Permission::where([ 'namespace' => $perm_namespace ])->first();
                    if ( $permission instanceof Permission ) {
                        RolePermission::where([ 
                            'role_id' => $role->id, 
                            'permission_id' => $permission->id, 
                        ])->delete();
                    }
                });
        });
    }

    /**
     * is used to remove a set of permission
     * attached to the 
     * @param Query
     * @param array of permissions
     * @return void
     */
    public function scopeAddPermissions( $query, $permissions )
    {
        $query
            ->get()
            ->each( function( $role ) use ( $permissions ) {
                collect( $permissions )->each( function( $perm_namespace ) use ( $role ) {
                    $permission     =   Permission::where([ 'namespace' => $perm_namespace ])->first();
                    if ( $permission instanceof Permission ) {
                        self::__createRelation( $role, $permission );
                    }
                });
        });
    }
}
