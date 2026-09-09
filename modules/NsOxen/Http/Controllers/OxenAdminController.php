<?php

namespace Modules\NsOxen\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\NsOxen\Http\Requests\SettingsRequest;
use Modules\NsOxen\Models\Operation;
use Modules\NsOxen\Models\Setting;
use Modules\NsOxen\Models\Usage;
use Modules\NsOxen\Services\OpenAIProvider;

class OxenAdminController extends Controller
{
    public function show(): JsonResponse
    {
        $s = Setting::query()->firstOrCreate( ['provider' => 'openai'] );

        return response()->json( ['provider' => $s->provider, 'model' => $s->model, 'image_model' => $s->image_model ?: 'gpt-image-2', 'assistant_enabled' => $s->assistant_enabled, 'writes_enabled' => $s->writes_enabled, 'daily_message_limit' => $s->daily_message_limit, 'output_token_limit' => $s->output_token_limit, 'context_token_limit' => $s->context_token_limit, 'tool_call_limit' => $s->tool_call_limit ?: 8, 'configured' => (bool) $s->api_key, 'key_suffix' => $s->api_key ? '••••' . substr( $s->api_key, -4 ) : null] );
    }

    public function update( SettingsRequest $request ): JsonResponse
    {
        $s = Setting::query()->firstOrCreate( ['provider' => 'openai'] );
        $data = $request->validated();
        if ( ( $data['assistant_enabled'] ?? false ) && ! ( $data['api_key'] ?? $s->api_key ) ) {
            return response()->json( ['code' => 'VALIDATION_FAILED', 'message' => __m( 'Configure an API key before enabling Oxen.', 'NsOxen' )], 422 );
        } $s->fill( $data )->save();

        return $this->show();
    }

    public function test( SettingsRequest $request, OpenAIProvider $provider ): JsonResponse
    {
        $s = Setting::query()->firstOrCreate( ['provider' => 'openai'] );
        $s->fill( $request->validated() );

        return response()->json( $provider->test( $s ) );
    }

    public function usage(): JsonResponse
    {
        return response()->json( Usage::query()->latest( 'usage_date' )->limit( 100 )->get() );
    }

    public function audit(): JsonResponse
    {
        return response()->json( Operation::query()->latest()->limit( 100 )->get( ['correlation_id', 'user_id', 'token_id', 'store_id', 'tool', 'status', 'duration_ms', 'created_at'] ) );
    }
}
