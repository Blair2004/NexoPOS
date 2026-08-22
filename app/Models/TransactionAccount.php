<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransactionAccount extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_transactions_accounts';

    public function transactions(): HasMany
    {
        return $this->hasMany( Transaction::class, 'account_id' );
    }

    public function scopeAccount( $query, $account )
    {
        return $query->where( 'account', $account );
    }

    public function scopeCategoryIdentifier( $query, $category )
    {
        return $query->where( 'category_identifier', $category );
    }

    public function histories(): HasMany
    {
        return $this->hasMany( TransactionHistory::class, 'transaction_account_id' );
    }

    public function ruleLines(): HasMany
    {
        return $this->hasMany( TransactionActionRuleLine::class, 'account_id' );
    }
}
