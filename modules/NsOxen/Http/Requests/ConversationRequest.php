<?php

namespace Modules\NsOxen\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConversationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ns()->allowedTo( 'ns.oxen.use' );
    }

    public function rules(): array
    {
        return ['title' => ['sometimes', 'string', 'max:160']];
    }
}
