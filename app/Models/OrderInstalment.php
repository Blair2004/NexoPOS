<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property float $amount
 * @property integer $order_id
 * @property boolean $paid
 * @property integer $payment_id
 * @property string $date
 */
class OrderInstalment extends NsModel
{
    use HasFactory;

    public $timestamps       =   false;
    
    protected $table    =   'nexopos_' . 'orders_instalments';
    
    protected $casts    =   [
        'paid' =>   'boolean'
    ];
}