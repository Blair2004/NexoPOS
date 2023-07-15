<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @deprecated App\Models\TransactionAccount is the replacement.
 */
class ExpenseCategory extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'expenses_categories';

    public function expenses()
    {
        return $this->hasMany( Expense::class, 'category_id' );
    }

    public function cashFlowHistories()
    {
        return $this->hasMany( TransactionHistory::class, 'expense_category_id' );
    }
}
