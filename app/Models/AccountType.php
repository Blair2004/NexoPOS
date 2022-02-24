<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class AccountType extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'expenses_categories';

    public function expenses()
    {
        return $this->hasMany( Expense::class, 'category_id' );
    }

    public function scopeAccount( $query, $account )
    {
        return $query->where( 'account', $account );
    }

    public function cashFlowHistories()
    {
        return $this->hasMany( CashFlow::class, 'expense_category_id' );
    }
}