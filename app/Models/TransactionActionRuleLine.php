<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionActionRuleLine extends NsModel
{
    use HasFactory;

    protected $table = 'nexopos_transactions_actions_rule_lines';

    protected $fillable = [
        'rule_id',
        'account_id',
        'dynamic_account_role',
        'effect',
        'amount_source',
        'display_order',
    ];

    public function rule(): BelongsTo
    {
        return $this->belongsTo( TransactionActionRule::class, 'rule_id' );
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo( TransactionAccount::class, 'account_id' );
    }
}
