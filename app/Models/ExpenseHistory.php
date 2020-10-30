<?php
namespace App\Models;

use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseHistory extends Model
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'expenses_history';

    public $casts    =   [
        'value'         =>  CurrencyCast::class,
        'created_at'    =>  DateCast::class
    ];

    public function expense()
    {
        return $this->belongsTo( Expense::class, 'expense_id' );
    }
}