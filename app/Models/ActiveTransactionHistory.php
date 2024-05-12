<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActiveTransactionHistory extends TransactionHistory
{
    protected static function booted()
    {
        static::addGlobalScope( 'active', function ( $builder ) {
            $builder->where( 'status', TransactionHistory::STATUS_ACTIVE );
        } );
    }
}
