<?php

namespace App\Models;

class ActiveTransactionHistory extends TransactionHistory
{
    protected static function booted()
    {
        static::addGlobalScope( 'active', function ( $builder ) {
            $builder->where( 'status', TransactionHistory::STATUS_ACTIVE );
        } );
    }
}
