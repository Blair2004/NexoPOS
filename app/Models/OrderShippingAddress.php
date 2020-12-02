<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderShippingAddress extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'orders_addresses';

    protected static function booted()
    {
        static::addGlobalScope( 'type', function( Builder $builder ) {
            $builder->where( 'type', 'shipping' );
        });

        static::creating( function( $address ) {
            $address->type  =   'shipping';
        });
        
        static::updating( function( $address ) {
            $address->type  =   'shipping';
        });
    }
}