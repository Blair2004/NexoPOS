<?php

namespace App\Models\Scopes;

use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Order;

class DriverOrderScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder | QueryBuilder $builder, Model $model): void
    {
        $builder->whereIn( 'payment_status', [ Order::PAYMENT_PAID, Order::PAYMENT_UNPAID, Order::PAYMENT_PARTIALLY  ] );
        $builder->whereIn( 'delivery_status', [ Order::DELIVERY_PENDING, Order::DELIVERY_ONGOING, Order::DELIVERY_DELIVERED ]);
        $builder->where( 'driver_id', Auth::id() );
    }
}
