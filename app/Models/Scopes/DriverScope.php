<?php

namespace App\Models\Scopes;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class DriverScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder | QueryBuilder $builder, Model $model): void
    {
        $builder->join( 'nexopos_users_roles_relations', 'nexopos_users_roles_relations.user_id', '=', 'nexopos_users.id' )
            ->join( 'nexopos_roles', 'nexopos_roles.id', '=', 'nexopos_users_roles_relations.role_id' )
            ->where( 'nexopos_roles.name', 'driver' );
    }
}
