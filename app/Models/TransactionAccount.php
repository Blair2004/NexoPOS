<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property integer $id
 * @property string $uuid
 * @property string $description
 * @property integer $author
 * @property \Carbon\Carbon $updated_at
*/
class TransactionAccount extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'transactions_accounts';

    public function transactions()
    {
        return $this->hasMany( Transaction::class, 'account_id' );
    }

    public function scopeAccount( $query, $account )
    {
        return $query->where( 'account', $account );
    }

    public function cashFlowHistories()
    {
        return $this->hasMany( TransactionHistory::class, 'expense_category_id' );
    }
}
