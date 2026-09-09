<?php

namespace Modules\NsOxen\Models;

use App\Models\NsModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends NsModel
{
    protected $table = 'nexopos_oxen_messages';

    protected $fillable = ['conversation_id', 'role', 'content', 'metadata', 'status', 'input_tokens', 'output_tokens'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'input_tokens' => 'integer', 'output_tokens' => 'integer'];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo( Conversation::class );
    }
}
