<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\UUIDObserver;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @deprecated App\Models\AccountType is the replacement.
 */
class ExpenseCategory extends NsModel
{
    use HasFactory;
    
    protected $table    =   'nexopos_' . 'expenses_categories';

    public function expenses()
    {
        return $this->hasMany( Expense::class, 'category_id' );
    }

    public function cashFlowHistories()
    {
        return $this->hasMany( CashFlow::class, 'expense_category_id' );
    }
}