<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingJournal extends NsModel
{
    use HasFactory;

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $table = 'nexopos_accounting_journals';

    protected $fillable = [
        'source_type',
        'source_id',
        'event',
        'rule_id',
        'name',
        'status',
        'author_id',
        'posted_at',
    ];

    protected function casts(): array
    {
        return [
            'posted_at' => 'datetime',
        ];
    }

    public function rule(): BelongsTo
    {
        return $this->belongsTo( TransactionActionRule::class, 'rule_id' );
    }

    public function lines(): HasMany
    {
        return $this->hasMany( TransactionHistory::class, 'journal_id' );
    }

    public function source(): MorphTo
    {
        return $this->morphTo( 'source', 'source_type', 'source_id' );
    }
}
