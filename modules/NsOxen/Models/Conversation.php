<?php

namespace Modules\NsOxen\Models;

use App\Models\NsModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends NsModel
{
    protected $table = 'nexopos_oxen_conversations';

    protected $fillable = [
        'public_id', 'user_id', 'title', 'provider', 'model', 'status', 'summary',
        'provider_response_id', 'provider_response_message_id', 'last_activity_at',
    ];

    protected function casts(): array
    {
        return ['provider_response_message_id' => 'integer', 'last_activity_at' => 'datetime'];
    }

    public function messages(): HasMany
    {
        return $this->hasMany( Message::class )->oldest();
    }
}
