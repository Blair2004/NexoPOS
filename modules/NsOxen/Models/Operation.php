<?php

namespace Modules\NsOxen\Models;

use App\Models\NsModel;

class Operation extends NsModel
{
    protected $table = 'nexopos_oxen_operations';

    protected $fillable = ['correlation_id', 'user_id', 'token_id', 'store_id', 'tool', 'redacted_input', 'input_hash', 'result_reference', 'status', 'duration_ms'];

    protected function casts(): array
    {
        return ['redacted_input' => 'array', 'duration_ms' => 'integer'];
    }
}
