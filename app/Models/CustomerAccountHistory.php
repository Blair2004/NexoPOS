<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CustomerAccountHistory extends Model
{
    use HasFactory;

    const OPERATION_DEDUCT  =   'deduct';
    const OPERATION_ADD     =   'add';

    protected $table    =   'nexopos_' . 'customers_account_history';

    public function customer()
    {
        return $this->hasOne( Customer::class, 'id', 'customer_id' );
    }
}