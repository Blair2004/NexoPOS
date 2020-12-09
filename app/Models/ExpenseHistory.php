<?php
namespace App\Models;

use App\Casts\CurrencyCast;
use App\Casts\DateCast;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseHistory extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'expenses_history';

    public $casts    =   [
        'value'         =>  CurrencyCast::class,
        'created_at'    =>  DateCast::class
    ];

    const STATUS_ACTIVE     =   'active';
    const STATUS_DELETING   =   'deleting';

    public function expense()
    {
        return $this->belongsTo( Expense::class, 'expense_id' );
    }

    public function scopeFrom( $query, $date )
    {
        return $query->where( 'created_at', '<=', $date );
    }

    public function scopeTo( $query, $date )
    {
        return $query->where( 'created_at', '>=', $date );
    }
}