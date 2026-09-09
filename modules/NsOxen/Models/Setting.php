<?php

namespace Modules\NsOxen\Models;

use App\Models\NsModel;

class Setting extends NsModel
{
    protected $table = 'nexopos_oxen_settings';

    protected $fillable = [ 'provider', 'api_key', 'model', 'image_model', 'assistant_enabled', 'writes_enabled', 'daily_message_limit', 'output_token_limit', 'context_token_limit', 'tool_call_limit' ];

    protected $hidden = [ 'api_key' ];

    protected function casts(): array
    {
        return [ 'api_key' => 'encrypted', 'assistant_enabled' => 'boolean', 'writes_enabled' => 'boolean', 'daily_message_limit' => 'integer', 'output_token_limit' => 'integer', 'context_token_limit' => 'integer', 'tool_call_limit' => 'integer' ];
    }
}
