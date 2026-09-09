<?php

namespace Modules\NsOxen\Models;

use App\Models\NsModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActionProposal extends NsModel
{
    protected $table = 'nexopos_oxen_action_proposals';

    protected $fillable = [
        'public_id', 'user_id', 'store_id', 'conversation_id', 'message_id', 'tool',
        'payload', 'input_hash', 'risk', 'requires_confirmation', 'status', 'expires_at',
        'idempotency_key', 'executed_at', 'rejected_at', 'result_reference',
    ];

    protected $hidden = ['payload', 'input_hash', 'idempotency_key'];

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'store_id' => 'integer',
            'requires_confirmation' => 'boolean',
            'expires_at' => 'datetime',
            'executed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo( Conversation::class );
    }
}
