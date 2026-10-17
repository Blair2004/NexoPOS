<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Gate;

class OrderAccessService
{
    public const READ_ALL = 'nexopos.read.orders';

    public const POS_READ_ALL = 'nexopos.pos.read-all-orders';

    public const POS_READ_OWN = 'nexopos.pos.read-own-orders';

    public function canAccess( User $user, Order $order ): bool
    {
        if ( Gate::forUser( $user )->any( [ self::READ_ALL, self::POS_READ_ALL ] ) ) {
            return true;
        }

        return Gate::forUser( $user )->allows( self::POS_READ_OWN ) && (int) $order->author_id === (int) $user->id;
    }

    public function scopeAccessible( Builder|QueryBuilder $query, User $user ): Builder|QueryBuilder
    {
        if ( Gate::forUser( $user )->any( [ self::READ_ALL, self::POS_READ_ALL ] ) ) {
            return $query;
        }

        if ( Gate::forUser( $user )->allows( self::POS_READ_OWN ) ) {
            return $query->where( ( new Order )->qualifyColumn( 'author_id' ), $user->id );
        }

        return $query->whereRaw( '1 = 0' );
    }

    public function authorizeOrFail( User $user, Order $order ): void
    {
        if ( ! $this->canAccess( $user, $order ) ) {
            throw ( new ModelNotFoundException )->setModel( Order::class, [$order->id] );
        }
    }
}
