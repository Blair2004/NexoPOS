<?php

namespace Modules\NsOxen\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Mockery;
use Modules\NsOxen\Models\Conversation;
use Modules\NsOxen\Models\Setting;
use Modules\NsOxen\Http\Requests\MessageRequest;
use Modules\NsOxen\Services\ActionProposalService;
use Modules\NsOxen\Services\ConversationContextBuilder;
use Modules\NsOxen\Services\ConversationAttachmentService;
use Modules\NsOxen\Services\OpenAIProvider;
use Modules\NsOxen\Services\ToolRegistry;
use Tests\TestCase;

class OxenGroundingTest extends TestCase
{
    use DatabaseTransactions;

    public function test_context_is_ordered_bounded_and_includes_trusted_page_and_action_outcomes(): void
    {
        $this->migrateOxenTables();
        $conversation = $this->conversation();
        $conversation->messages()->create( ['role' => 'user', 'content' => 'First question', 'metadata' => ['route_name' => 'ns.dashboard.products-edit', 'entity_id' => 42]] );
        $conversation->messages()->create( ['role' => 'assistant', 'content' => 'First answer'] );
        $conversation->messages()->create( [
            'role' => 'assistant',
            'content' => 'Action approved and completed: Update products.',
            'metadata' => ['action_event' => ['proposal_id' => '<unsafe>', 'decision' => 'approved', 'tool' => 'update_products']],
        ] );
        $current = $conversation->messages()->create( ['role' => 'user', 'content' => 'What changed?', 'metadata' => []] );

        $context = app( ConversationContextBuilder::class )->build( $conversation, $current, 1000 );

        $this->assertSame( 'First question', $context[0]['content'] );
        $this->assertSame( 'First answer', $context[1]['content'] );
        $this->assertStringStartsWith( 'Trusted action outcome:', $context[2]['content'] );
        $this->assertStringStartsWith( 'Trusted current page context', $context[3]['content'] );
        $this->assertSame( ['role' => 'user', 'content' => 'What changed?'], $context[4] );
        $this->assertLessThanOrEqual( 4000, collect( $context )->sum( fn ( array $item ): int => mb_strlen( $item['content'] ) ) );

        $bounded = app( ConversationContextBuilder::class )->build( $conversation, $current, 4 );
        $this->assertSame( [['role' => 'user', 'content' => 'What changed?']], $bounded );
    }

    public function test_sales_yesterday_capability_boilerplate_is_retried_with_required_read_tools(): void
    {
        Http::preventStrayRequests();
        Http::fakeSequence( 'api.openai.com/v1/responses' )
            ->push( ['output' => [['content' => [['type' => 'output_text', 'text' => '{"markdown":"I can check yesterday sales for you.","conversation_title":"Yesterday Sales"}']]]]] )
            ->push( ['output' => [['type' => 'function_call', 'name' => 'get_dashboard_summary', 'call_id' => 'sales-call', 'arguments' => '{"date_start":"2026-09-05","date_end":"2026-09-05"}']]] )
            ->push( ['output' => [['content' => [['type' => 'output_text', 'text' => '{"markdown":"Yesterday paid sales were $250.","conversation_title":"Yesterday Sales"}']]]]] );

        $definition = app( ToolRegistry::class )->definition( 'get_dashboard_summary' );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'availableDefinitions' )->once()->andReturn( ['get_dashboard_summary' => $definition] );
        $registry->shouldReceive( 'definition' )->once()->with( 'get_dashboard_summary' )->andReturn( $definition );
        $registry->shouldReceive( 'execute' )->once()->withArgs( fn ( User $user, string $name, array $arguments ): bool => $name === 'get_dashboard_summary' && $arguments['date_start'] === '2026-09-05' )->andReturn( ['ok' => true, 'data' => ['paid_sales' => 250]] );
        $setting = new Setting( ['api_key' => 'sk-test', 'model' => 'gpt-test', 'output_token_limit' => 500] );

        $result = app( OpenAIProvider::class )->respond(
            $setting,
            [['role' => 'user', 'content' => 'What were our sales yesterday?']],
            User::query()->firstOrFail(),
            $registry,
            Mockery::mock( Conversation::class ),
            Mockery::mock( ActionProposalService::class ),
        );

        $this->assertSame( 'Yesterday paid sales were $250.', $result['markdown'] );
        $requests = Http::recorded()->pluck( 0 )->values();
        $this->assertCount( 3, $requests );
        $this->assertSame( 'required', data_get( $requests[1]->data(), 'tool_choice.mode' ) );
        $this->assertSame( 'allowed_tools', data_get( $requests[1]->data(), 'tool_choice.type' ) );
        $this->assertArrayNotHasKey( 'tool_choice', $requests[2]->data() );
    }

    public function test_attachment_is_stored_privately_and_sent_as_an_openai_file_input(): void
    {
        $this->migrateOxenTables();
        Storage::fake( 'local' );
        Http::preventStrayRequests();
        Http::fake( ['api.openai.com/v1/responses' => Http::response( [
            'output' => [['content' => [['type' => 'output_text', 'text' => '{"markdown":"The CSV contains one product.","conversation_title":"CSV Import"}']]]],
        ] )] );

        $conversation = $this->conversation();
        $message = $conversation->messages()->create( ['role' => 'user', 'content' => 'Import this file', 'metadata' => []] );
        $attachmentService = app( ConversationAttachmentService::class );
        $stored = $attachmentService->store( $conversation, $message, [
            UploadedFile::fake()->createWithContent( 'sample-products.csv', "name,price\nCoffee,12" ),
        ] );
        $message->update( ['metadata' => ['attachments' => $stored]] );
        $message->refresh();

        $context = app( ConversationContextBuilder::class )->build( $conversation, $message, 12000 );
        $result = app( OpenAIProvider::class )->respond(
            new Setting( ['api_key' => 'sk-test', 'model' => 'gpt-test', 'output_token_limit' => 500] ),
            $context,
            User::query()->firstOrFail(),
            Mockery::mock( ToolRegistry::class, function ( $mock ): void {
                $mock->shouldReceive( 'availableDefinitions' )->once()->andReturn( [] );
            } ),
        );

        $this->assertSame( 'The CSV contains one product.', $result['markdown'] );
        $this->assertSame( 'sample-products.csv', data_get( $context, '0.content.1.filename' ) );
        $this->assertStringStartsWith( 'data:text/csv;base64,', data_get( $context, '0.content.1.file_data' ) );
        $this->assertArrayNotHasKey( 'path', $attachmentService->present( $stored )[0] );
        Storage::disk( 'local' )->assertExists( $stored[0]['path'] );
        Http::assertSent( fn ( $request ): bool => data_get( $request->data(), 'input.1.content.1.type' ) === 'input_file'
            && data_get( $request->data(), 'input.1.content.1.filename' ) === 'sample-products.csv' );

        $conversation->messages()->create( ['role' => 'assistant', 'content' => 'The CSV contains one product.'] );
        $followUp = $conversation->messages()->create( ['role' => 'user', 'content' => 'What was its price?', 'metadata' => []] );
        $followUpContext = app( ConversationContextBuilder::class )->build( $conversation, $followUp, 12000 );
        $this->assertSame( 'input_file', data_get( $followUpContext, '0.content.1.type' ) );
        $this->assertSame( 'sample-products.csv', data_get( $followUpContext, '0.content.1.filename' ) );

        $attachmentService->deleteConversation( $conversation );
        Storage::disk( 'local' )->assertMissing( $stored[0]['path'] );
    }

    public function test_attachment_request_rejects_missing_unsupported_and_excess_files(): void
    {
        $rules = ( new MessageRequest )->rules();
        $valid = Validator::make( [
            'attachments' => [UploadedFile::fake()->createWithContent( 'products.csv', "name,price\nCoffee,12" )],
        ], $rules );
        $missing = Validator::make( [], $rules );
        $unsupported = Validator::make( [
            'attachments' => [UploadedFile::fake()->createWithContent( 'script.php', '<?php echo 1;')],
        ], $rules );
        $excess = Validator::make( [
            'attachments' => collect( range( 1, 5 ) )->map( fn ( int $index ): UploadedFile => UploadedFile::fake()->createWithContent( "{$index}.txt", 'text' ) )->all(),
        ], $rules );

        $this->assertFalse( $valid->fails() );
        $this->assertTrue( $missing->fails() );
        $this->assertTrue( $unsupported->fails() );
        $this->assertTrue( $excess->fails() );
    }

    public function test_identity_answers_do_not_force_a_tool_call(): void
    {
        Http::preventStrayRequests();
        Http::fake( ['api.openai.com/v1/responses' => Http::response( [
            'output' => [['content' => [['type' => 'output_text', 'text' => '{"markdown":"I am Oxen, your store assistant.","conversation_title":"About Oxen"}']]]],
        ] )] );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'availableDefinitions' )->once()->andReturn( [] );

        $result = app( OpenAIProvider::class )->respond(
            new Setting( ['api_key' => 'sk-test', 'model' => 'gpt-test', 'output_token_limit' => 500] ),
            [['role' => 'user', 'content' => 'Who are you?']],
            User::query()->firstOrFail(),
            $registry,
        );

        $this->assertSame( 'I am Oxen, your store assistant.', $result['markdown'] );
        Http::assertSentCount( 1 );
    }

    public function test_image_generation_uses_the_configured_model_size_and_transparent_png_output(): void
    {
        Http::preventStrayRequests();
        Http::fake( ['api.openai.com/v1/images/generations' => Http::response( ['data' => [['b64_json' => 'encoded-image']]] )] );
        $setting = new Setting( ['api_key' => 'sk-test', 'model' => 'gpt-test', 'image_model' => 'gpt-image-2'] );

        $result = app( OpenAIProvider::class )->generateImage( $setting, 'A clean store logo', '1536x1024', true );

        $this->assertSame( 'encoded-image', $result );
        Http::assertSent( fn ( $request ): bool => $request['model'] === 'gpt-image-2'
            && $request['size'] === '1536x1024'
            && $request['output_format'] === 'png'
            && $request['background'] === 'transparent'
            && $request['n'] === 1 );
    }

    private function conversation(): Conversation
    {
        return Conversation::query()->create( [
            'public_id' => (string) Str::uuid(),
            'user_id' => User::query()->firstOrFail()->id,
            'title' => 'Context test',
            'provider' => 'openai',
            'model' => 'gpt-test',
            'status' => 'active',
            'last_activity_at' => now(),
        ] );
    }

    private function migrateOxenTables(): void
    {
        ( require dirname( __DIR__, 2 ) . '/Migrations/2026_09_04_000000_create_oxen_tables.php' )->up();
    }
}
