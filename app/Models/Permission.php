<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int id
 * @property string name
 * @property string namespace
 * @property string description
 */
class Permission extends Model
{
    use HasFactory;

    protected $table = 'nexopos_permissions';

    protected $fillable = [ 'namespace', 'name', 'description' ];

    public function scopeWithNamespace( $query, $param )
    {
        return $query->where( 'namespace', $param );
    }

    /**
     * Get Name
     *
     * @param string permission name
     * @return Permission
     **/
    public static function namespace( $name )
    {
        return self::where( 'namespace', $name )->first();
    }

    public static function withNamespaceOrNew( $name )
    {
        $instance = self::where( 'namespace', $name )->first();

        return $instance instanceof self ? $instance : new self;
    }

    public function roles()
    {
        return $this->belongsToMany( Role::class, 'nexopos_role_permission' );
    }

    /**
     * return all permissions using
     * a namespace string used for search
     *
     * @param Query
     * @param string search namespace
     * @return Query
     */
    public function scopeIncludes( $query, $search )
    {
        return $query->where( 'namespace', 'like', '%' . $search . '%' );
    }

    /**
     * Will remove a permissions
     * from all roles. By destroying the relation that might
     * exist with that permission.
     *
     * @return void
     */
    public function removeFromRoles()
    {
        RolePermission::where( 'permission_id', $this->id )->delete();
    }
}
