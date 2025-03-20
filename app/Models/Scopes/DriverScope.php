<?php

namespace App\Models\Scopes;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;

class DriverScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder | QueryBuilder $builder, Model $model): void
    {
        $builder->addSelect( 'nexopos_users.id as id' );
        $builder->addSelect( 'nexopos_users.email as email' );
        $builder->addSelect( 'nexopos_users.username as username' );
        $builder->addSelect( 'nexopos_users.active as active' );
        $builder->addSelect( 'nexopos_users.author as author' );
        $builder->addSelect([
            'status'    =>  DB::table( 'nexopos_drivers_statuses' )
                ->select( 'status' )
                ->whereColumn( 'nexopos_drivers_statuses.driver_id', 'nexopos_users.id' )
                ->latest()
                ->limit( 1 )
        ]);
        
        $builder->join( 'nexopos_users_roles_relations', 'nexopos_users_roles_relations.user_id', '=', 'nexopos_users.id' )
            ->join( 'nexopos_roles', 'nexopos_roles.id', '=', 'nexopos_users_roles_relations.role_id' )
            ->where( 'nexopos_roles.name', 'driver' );
    }
}
