<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int            $id
 * @property string         $uuid
 * @property string         $description
 * @property int            $author
 * @property \Carbon\Carbon $updated_at
 */
class TransactionAccount extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_' . 'transactions_accounts';

    public function scopeCredit( $query )
    {
        return $query->where( 'operation', 'credit' );
    }

    public function scopeDebit( $query )
    {
        return $query->where( 'operation', 'debit' );
    }

    public function transactions()
    {
        return $this->hasMany( Transaction::class, 'account_id' );
    }

    public function scopeAccount( $query, $account )
    {
        return $query->where( 'account', $account );
    }

    public function histories()
    {
        return $this->hasMany( TransactionHistory::class, 'transaction_account_id' );
    }
}
