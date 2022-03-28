<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method combinaison( User $user, Role $role )
 */
class UserRoleRelation extends Model
{
    protected $table = 'nexopos_users_roles_relations';

    use HasFactory;

    public function scopeCombinaison( $query, $user, $role )
    {
        return $query->where( 'user_id', $user->id )
            ->where( 'role_id', $role->id );
    }
}
