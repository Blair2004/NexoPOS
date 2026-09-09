<?php

namespace Modules\NsOxen\Models;

use App\Models\NsModel;

class IdempotencyRecord extends NsModel
{
    protected $table = 'nexopos_oxen_idempotency';

    protected $fillable = ['user_id', 'tool', 'idempotency_key', 'input_hash', 'result', 'expires_at'];

    protected function casts(): array
    {
        return ['result' => 'array', 'expires_at' => 'datetime'];
    }
}
