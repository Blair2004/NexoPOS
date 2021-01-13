<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderTax extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'orders_taxes';
    public $timestamps  =   false;

    public function order()
    {
        return $this->belongsTo( Order::class, 'id', 'order_id' );
    }
}