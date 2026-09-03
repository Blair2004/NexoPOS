<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int   $id
 * @property float $opening_balance
 * @property float $closing_balance
 */
class TransactionBalanceDay extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_transactions_balance_days';
}
