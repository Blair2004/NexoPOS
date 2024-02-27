<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property int            $author
 * @property string         $uuid
 * @property \Carbon\Carbon $updated_at
 */
class CustomerBillingAddress extends CustomerAddress
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope( 'type', function ( Builder $builder ) {
            $builder->where( 'type', 'billing' );
        } );

        static::creating( function ( $address ) {
            $address->type = 'billing';
        } );

        static::updating( function ( $address ) {
            $address->type = 'billing';
        } );
    }
}
