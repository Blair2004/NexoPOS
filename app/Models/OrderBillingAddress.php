<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderBillingAddress extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'orders_addresses';

    protected static function booted()
    {
        static::addGlobalScope( 'type', function ( Builder $builder ) {
            $builder->where( 'type', 'billing' );
        });

        static::creating( function ( $address ) {
            $address->type = 'billing';
        });

        static::updating( function ( $address ) {
            $address->type = 'billing';
        });
    }
}
