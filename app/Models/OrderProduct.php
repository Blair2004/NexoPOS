<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderProduct extends Model
{
    use HasFactory;

    const CONDITION_DAMAGED     =   'damaged';
    const CONDITION_UNSPOILED   =   'unspoiled';
    
    protected $table    =   'nexopos_' . 'orders_products';

    public function unit()
    {
        return $this->hasOne( Unit::class, 'id', 'unit_id' );
    }

    public function order()
    {
        return $this->belongsTo( Order::class, 'order_id', 'id' );
    }

    public function product()
    {
        return $this->hasOne( Product::class, 'id', 'product_id' );
    }
}