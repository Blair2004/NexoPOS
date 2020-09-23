<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'orders';

    public function products()
    {
        return $this->hasMany(
            OrderProduct::class,
            'order_id'
        );
    }

    public function payments()
    {
        return $this->hasMany( OrderPayment::class, 'order_id' );
    }
}