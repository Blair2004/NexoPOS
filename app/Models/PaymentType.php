<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentType extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_payments_types';

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'readonly' => 'boolean',
            'accounting_account_id' => 'integer',
        ];
    }

    public function scopeActive( $query )
    {
        return $query->where( 'active', true );
    }

    public function scopeIdentifier( $query, $identifier )
    {
        return $query->where( 'identifier', $identifier );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo( User::class, 'author_id' );
    }

    public function accountingAccount(): BelongsTo
    {
        return $this->belongsTo( TransactionAccount::class, 'accounting_account_id' );
    }
}
