<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property int   $id
 * @property float $opening_balance
 * @property float $income
 * @property float $expense
 * @property float $closing_balance
 * @property mixed $date
 * @property mixed $created_at
 * @property mixed $updated_at
 */
class TransactionBalanceMonth extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_transactions_balance_months';
}
