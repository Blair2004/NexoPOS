<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Role;

class Permission extends Model
{
    protected $table    =   'nexopos_permissions';

    public function scopeNamespace( $query, $param ) {
        return $query->where( 'namespace', $param );
    }

    public function roles()
    {
        return $this->belongsToMany( Role::class, 'nexopos_role_permission' );
    }

    /**
     * return all permissions using
     * a namespace string used for search
     * @param Query
     * @param string search namespace
     * @return Query
     */
    public function scopeIncludes( $query, $search )
    {   
        return $query->where( 'namespace', 'like', '%' . $search );
    }
}
