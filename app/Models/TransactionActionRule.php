<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionActionRule extends Model
{
    use HasFactory;

    protected $fillable     =   [ 'on', 'action', 'account_id', 'do', 'offset_account_id', 'locked' ];

    protected $table = 'nexopos_transactions_actions_rules';
}
