<?php

namespace Modules\NsOxen\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Mockery;
use Modules\NsOxen\Http\Requests\SettingsRequest;
use Modules\NsOxen\Models\Setting;
use Modules\NsOxen\Services\OpenAIProvider;
use Modules\NsOxen\Services\OxenException;
use Modules\NsOxen\Services\ToolRegistry;
use Tests\TestCase;

class OpenAIProviderSettingsTest extends TestCase
{
    public function test_response_chain_uses_incremental_input_and_server_side_compaction(): void
    {
        Http::preventStrayRequests();
        Http::fakeSequence( 'api.openai.com/v1/responses' )
            ->push( [
                'id' => 'resp-tool',
                'output' => [[
                    'type' => 'function_call',
                    'name' => 'get_store_settings',
                    'call_id' => 'settings-call',
                    'arguments' => '{}',
                ]],
            ] )
            ->push( [
                'id' => 'resp-final',
                'output' => [[
                    'content' => [[
                        'type' => 'output_text',
                        'text' => '{"markdown":"Current settings loaded.","conversation_title":"Store Settings"}',
                    ]],
                ]],
            ] );

        $definition = app( ToolRegistry::class )->definition( 'get_store_settings' );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'availableDefinitions' )->once()->andReturn( ['get_store_settings' => $definition] );
        $registry->shouldReceive( 'definition' )->once()->with( 'get_store_settings' )->andReturn( $definition );
        $registry->shouldReceive( 'execute' )->once()->andReturn( ['ok' => true, 'data' => ['name' => 'Demo']] );

        $result = app( OpenAIProvider::class )->respond(
            new Setting( [
                'api_key' => 'sk-test-secret',
                'model' => 'gpt-test',
                'output_token_limit' => 321,
                'context_token_limit' => 9000,
            ] ),
            [
                ['role' => 'user', 'content' => 'An older question'],
                ['role' => 'assistant', 'content' => 'An older answer'],
                ['role' => 'user', 'content' => 'Load the current settings'],
            ],
            Mockery::mock( User::class ),
            $registry,
            previousResponseId: 'resp-previous',
            continuationInput: [['role' => 'user', 'content' => 'Load the current settings']],
        );

        $this->assertSame( 'resp-final', $result['response_id'] );
        $requests = Http::recorded()->pluck( 0 )->values();
        $this->assertCount( 2, $requests );
        $this->assertSame( 'resp-previous', $requests[0]->data()['previous_response_id'] );
        $this->assertSame( 9000, data_get( $requests[0]->data(), 'context_management.0.compact_threshold' ) );
        $this->assertTrue( $requests[0]->data()['store'] );
        $this->assertStringStartsWith( 'Trusted NexoPOS system context', data_get( $requests[0]->data(), 'input.0.content' ) );
        $this->assertSame( 'Load the current settings', data_get( $requests[0]->data(), 'input.1.content' ) );
        $this->assertStringNotContainsString( 'An older answer', json_encode( $requests[0]->data()['input'], JSON_THROW_ON_ERROR ) );
        $this->assertSame( 'resp-tool', $requests[1]->data()['previous_response_id'] );
        $this->assertSame( 'function_call_output', data_get( $requests[1]->data(), 'input.0.type' ) );
    }

    public function test_invalid_previous_response_retries_once_with_bounded_local_history(): void
    {
        Http::preventStrayRequests();
        Http::fakeSequence( 'api.openai.com/v1/responses' )
            ->push( ['error' => ['code' => 'response_not_found', 'param' => 'previous_response_id']], 404 )
            ->push( [
                'id' => 'resp-recovered',
                'output' => [['content' => [['type' => 'output_text', 'text' => '{"markdown":"Recovered.","conversation_title":"Recovered Chain"}']]]],
            ] );

        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'availableDefinitions' )->once()->andReturn( [] );
        $result = app( OpenAIProvider::class )->respond(
            new Setting( ['api_key' => 'sk-test', 'model' => 'gpt-test', 'output_token_limit' => 500, 'context_token_limit' => 12000] ),
            [
                ['role' => 'assistant', 'content' => 'Local history checkpoint'],
                ['role' => 'user', 'content' => 'Continue'],
            ],
            Mockery::mock( User::class ),
            $registry,
            previousResponseId: 'resp-expired',
            continuationInput: [['role' => 'user', 'content' => 'Continue']],
        );

        $this->assertSame( 'resp-recovered', $result['response_id'] );
        $requests = Http::recorded()->pluck( 0 )->values();
        $this->assertSame( 'resp-expired', $requests[0]->data()['previous_response_id'] );
        $this->assertArrayNotHasKey( 'previous_response_id', $requests[1]->data() );
        $this->assertStringContainsString( 'Local history checkpoint', json_encode( $requests[1]->data()['input'], JSON_THROW_ON_ERROR ) );
    }

    public function test_unrelated_provider_validation_error_does_not_reset_the_response_chain(): void
    {
        Http::preventStrayRequests();
        Http::fake( ['api.openai.com/v1/responses' => Http::response( [
            'error' => ['code' => 'invalid_value', 'param' => 'tools'],
        ], 400 )] );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'availableDefinitions' )->once()->andReturn( [] );

        try {
            app( OpenAIProvider::class )->respond(
                new Setting( ['api_key' => 'sk-test', 'model' => 'gpt-test', 'output_token_limit' => 500, 'context_token_limit' => 12000] ),
                [['role' => 'user', 'content' => 'Continue']],
                Mockery::mock( User::class ),
                $registry,
                previousResponseId: 'resp-existing',
                continuationInput: [['role' => 'user', 'content' => 'Continue']],
            );
            $this->fail( 'The provider exception was not thrown.' );
        } catch ( OxenException $exception ) {
            $this->assertSame( 'PROVIDER_UNAVAILABLE', $exception->errorCode );
        }

        Http::assertSentCount( 1 );
    }

    public function test_unsupported_compaction_is_retried_without_disabling_response_chaining(): void
    {
        Http::preventStrayRequests();
        Http::fakeSequence( 'api.openai.com/v1/responses' )
            ->push( ['error' => ['code' => 'unsupported_parameter', 'param' => 'context_management']], 400 )
            ->push( [
                'id' => 'resp-without-compaction',
                'output' => [['content' => [['type' => 'output_text', 'text' => '{"markdown":"Continued.","conversation_title":"Continued Chain"}']]]],
            ] );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'availableDefinitions' )->once()->andReturn( [] );

        $result = app( OpenAIProvider::class )->respond(
            new Setting( ['api_key' => 'sk-test', 'model' => 'gpt-test', 'output_token_limit' => 500, 'context_token_limit' => 12000] ),
            [['role' => 'user', 'content' => 'Continue']],
            Mockery::mock( User::class ),
            $registry,
            previousResponseId: 'resp-existing',
            continuationInput: [['role' => 'user', 'content' => 'Continue']],
        );

        $this->assertSame( 'resp-without-compaction', $result['response_id'] );
        $requests = Http::recorded()->pluck( 0 )->values();
        $this->assertArrayHasKey( 'context_management', $requests[0]->data() );
        $this->assertArrayNotHasKey( 'context_management', $requests[1]->data() );
        $this->assertSame( 'resp-existing', $requests[1]->data()['previous_response_id'] );
    }

    public function test_tool_call_limit_accepts_longer_turns_and_persists_one_activity_per_call(): void
    {
        Http::preventStrayRequests();
        $sequence = Http::fakeSequence( 'api.openai.com/v1/responses' );

        foreach ( range( 1, 9 ) as $callNumber ) {
            $sequence->push( [
                'output' => [[
                    'type' => 'function_call',
                    'name' => 'get_store_settings',
                    'call_id' => "call-{$callNumber}",
                    'arguments' => '{}',
                ]],
            ] );
        }

        $sequence->push( [
            'output' => [[
                'content' => [[
                    'type' => 'output_text',
                    'text' => '{"markdown":"Finished.","conversation_title":"Long Tool Turn"}',
                ]],
            ]],
        ] );

        $definition = app( ToolRegistry::class )->definition( 'get_store_settings' );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'availableDefinitions' )->once()->andReturn( ['get_store_settings' => $definition] );
        $registry->shouldReceive( 'definition' )->times( 9 )->with( 'get_store_settings' )->andReturn( $definition );
        $registry->shouldReceive( 'execute' )->times( 9 )->andReturn( ['ok' => true, 'data' => []] );
        $liveEvents = [];

        $result = app( OpenAIProvider::class )->respond(
            new Setting( [
                'api_key' => 'sk-test-secret',
                'model' => 'gpt-test',
                'output_token_limit' => 321,
                'tool_call_limit' => 9,
            ] ),
            [['role' => 'user', 'content' => 'Complete a long multi-step task']],
            Mockery::mock( User::class ),
            $registry,
            onToolEvent: function ( string $event, array $data ) use ( &$liveEvents ): void {
                $liveEvents[] = compact( 'event', 'data' );
            },
        );

        $this->assertSame( 'Finished.', $result['markdown'] );
        $this->assertCount( 9, $result['tool_events'] );
        $this->assertSame( array_fill( 0, 9, 'tool.completed' ), array_column( $result['tool_events'], 'event' ) );
        $this->assertCount( 18, $liveEvents );
        Http::assertSentCount( 10 );
    }

    public function test_tool_call_limit_setting_is_bounded(): void
    {
        $rules = ( new SettingsRequest )->rules();

        $this->assertTrue( Validator::make( ['tool_call_limit' => 16], $rules )->passes() );
        $this->assertTrue( Validator::make( ['tool_call_limit' => 64], $rules )->passes() );
        $this->assertFalse( Validator::make( ['tool_call_limit' => 0], $rules )->passes() );
        $this->assertFalse( Validator::make( ['tool_call_limit' => 65], $rules )->passes() );
        $this->assertFalse( Validator::make( ['tool_call_limit' => 'many'], $rules )->passes() );
    }
}
