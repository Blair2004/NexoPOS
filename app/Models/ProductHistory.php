<?php
namespace App\Models;

use App\Models\Unit;
use Illuminate\Database\Eloquent\Model;

class ProductHistory extends Model
{
    protected $table    =   'nexopos_' . 'products_histories';

    public function scopeFindProduct( $query, $id )
    {
        return $query->where( 'product_id', $id );
    }

    public function unit()
    {
        return $this->belongsTo( Unit::class );
    }
}