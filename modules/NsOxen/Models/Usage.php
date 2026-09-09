<?php

namespace Modules\NsOxen\Models;

use App\Models\NsModel;

class Usage extends NsModel
{
    protected $table = 'nexopos_oxen_usage';

    protected $fillable = ['user_id', 'usage_date', 'messages', 'input_tokens', 'output_tokens'];

    protected function casts(): array
    {
        return ['usage_date' => 'date', 'messages' => 'integer', 'input_tokens' => 'integer', 'output_tokens' => 'integer'];
    }
}
