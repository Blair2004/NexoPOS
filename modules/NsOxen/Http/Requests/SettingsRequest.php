<?php

namespace Modules\NsOxen\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ns()->allowedTo( 'ns.oxen.manage' );
    }

    public function rules(): array
    {
        return ['api_key' => ['sometimes', 'nullable', 'string', 'starts_with:sk-', 'max:500'], 'model' => ['sometimes', 'string', 'max:120'], 'image_model' => ['sometimes', 'string', 'max:120'], 'assistant_enabled' => ['sometimes', 'boolean'], 'writes_enabled' => ['sometimes', 'boolean'], 'daily_message_limit' => ['sometimes', 'integer', 'between:1,1000'], 'output_token_limit' => ['sometimes', 'integer', 'between:128,8000'], 'context_token_limit' => ['sometimes', 'integer', 'between:1000,100000'], 'tool_call_limit' => ['sometimes', 'integer', 'between:1,64']];
    }
}
