<?php

namespace Modules\NsOxen\Tests\Feature;

use App\Models\Product as NexoPOSProduct;
use App\Models\ProductCategory as NexoPOSProductCategory;
use App\Models\User;
use App\Services\DateService;
use App\Services\ModulesService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use Mockery;
use Modules\NsOxen\Contracts\OxenToolContract;
use Modules\NsOxen\Data\OxenExecutionContext;
use Modules\NsOxen\Data\OxenToolDefinition;
use Modules\NsOxen\Data\OxenToolResult;
use Modules\NsOxen\Http\Controllers\OxenController;
use Modules\NsOxen\Http\Requests\MessageRequest;
use Modules\NsOxen\Mcp\Tools\OxenTool;
use Modules\NsOxen\Models\ActionProposal;
use Modules\NsOxen\Models\Conversation;
use Modules\NsOxen\Models\Message;
use Modules\NsOxen\Models\Product;
use Modules\NsOxen\Models\ProductCategory;
use Modules\NsOxen\Models\Setting;
use Modules\NsOxen\Services\ActionProposalService;
use Modules\NsOxen\Services\AuthorizeOxenOperation;
use Modules\NsOxen\Services\CatalogCreationService;
use Modules\NsOxen\Services\ConversationAttachmentService;
use Modules\NsOxen\Services\ConversationContextBuilder;
use Modules\NsOxen\Services\OpenAIProvider;
use Modules\NsOxen\Services\OxenException;
use Modules\NsOxen\Services\ProductManagementService;
use Modules\NsOxen\Services\SafeWriteTools;
use Modules\NsOxen\Services\StoreClock;
use Modules\NsOxen\Services\ToolRegistry;
use Modules\NsOxen\Services\TrustedSystemContext;
use Tests\TestCase;

class OxenContractTest extends TestCase
{
    use DatabaseTransactions;

    private ?DateService $originalDateService = null;

    private ?string $originalApplicationTimezone = null;

    private ?string $originalPhpTimezone = null;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        if ( $this->originalDateService ) {
            ns()->date = $this->originalDateService;
        }
        if ( $this->originalApplicationTimezone ) {
            config( ['app.timezone' => $this->originalApplicationTimezone] );
        }
        if ( $this->originalPhpTimezone ) {
            date_default_timezone_set( $this->originalPhpTimezone );
        }

        parent::tearDown();
    }

    public function test_registry_exposes_the_core_inventory(): void
    {
        $registry = app( ToolRegistry::class );
        $this->assertEqualsCanonicalizing( [
            'search_products', 'get_product', 'get_low_stock_products', 'search_customers', 'get_customer',
            'search_orders', 'get_order', 'search_product_sales', 'search_wallet_history', 'get_dashboard_summary',
            'get_product_sales_performance', 'generate_report', 'create_product', 'create_provider', 'create_customer',
            'import_products', 'create_product_category', 'update_product_category', 'update_product', 'update_products',
            'update_product_unit_quantities', 'generate_product_image', 'update_settings',
            'upload_media', 'update_media', 'delete_media', 'generate_store_logo',
            'create_unit_group', 'update_unit_group', 'create_unit', 'update_unit',
            'create_tax_group', 'update_tax_group', 'create_tax', 'update_tax',
            'create_coupon', 'update_coupon', 'create_customer_group', 'update_customer_group', 'get_coupon', 'search_tax_groups',
            'get_inventory_quantities', 'search_stock_history', 'search_categories', 'search_units', 'search_unit_groups',
            'search_customer_groups', 'search_rewards', 'search_coupons', 'search_providers', 'search_procurements',
            'search_registers', 'search_register_history', 'search_taxes', 'search_media', 'get_payment_method_totals',
            'get_cashier_ranking', 'get_refund_summary', 'get_yearly_sales',
            'search_installments', 'search_transactions', 'search_accounts', 'compare_sales_periods',
            'get_profit_summary', 'get_store_settings',
        ], $registry->names() );
    }

    public function test_dashboard_summary_uses_the_existing_sales_report_permission(): void
    {
        $definition = app( ToolRegistry::class )->definition( 'get_dashboard_summary' );

        $this->assertNotNull( $definition );
        $this->assertSame( ['nexopos.reports.sales'], $definition->permissions );
        $this->assertSame( 'read', $definition->risk );
    }

    public function test_launcher_renders_user_message_content_only_in_the_user_branch(): void
    {
        $launcher = file_get_contents( dirname( __DIR__, 2 ) . '/Resources/ts/components/Launcher.vue' );

        $this->assertIsString( $launcher );
        $this->assertStringContainsString(
            'v-if="message.type !== \'info\' && message.role !== \'user\' && message.content"',
            $launcher,
        );
        $this->assertStringNotContainsString(
            'v-else-if="message.type !== \'info\' && message.content"',
            $launcher,
        );
        $this->assertStringContainsString(
            "const assistant = messages.value[messages.value.length - 1]",
            $launcher,
        );
        $this->assertStringNotContainsString( 'messages.value.push(assistant)', $launcher );
        $this->assertStringContainsString( "Accept: 'text/event-stream'", $launcher );
    }

    public function test_every_definition_has_valid_schemas_and_mcp_metadata(): void
    {
        foreach ( app( ToolRegistry::class )->definitions() as $definition ) {
            $this->assertInstanceOf( OxenToolDefinition::class, $definition );
            $this->assertSame( 'object', $definition->inputSchema['type'] );
            $this->assertSame( 'object', $definition->outputSchema['type'] );

            $mcp = ( new OxenTool( $definition ) )->toArray();
            $this->assertSame( $definition->name, $mcp['name'] );
            $this->assertSame( $definition->inputSchema, $mcp['inputSchema'] );
        }
    }

    public function test_openai_tool_schemas_encode_empty_properties_as_an_object(): void
    {
        $tool = app( ToolRegistry::class )->definition( 'get_store_settings' )?->forOpenAI();
        $settingsTool = app( ToolRegistry::class )->definition( 'update_settings' )?->forOpenAI();

        $this->assertNotNull( $tool );
        $this->assertNotNull( $settingsTool );
        $this->assertSame( '{}', json_encode( $tool['parameters']['properties'], JSON_THROW_ON_ERROR ) );
        $this->assertArrayNotHasKey( 'idempotency_key', $settingsTool['parameters']['properties'] );
        $this->assertNotContains( 'idempotency_key', $settingsTool['parameters']['required'] );
        $this->assertArrayHasKey( 'ns_store_name', $settingsTool['parameters']['properties']['settings']['properties'] );
    }

    public function test_failed_assistant_turn_is_streamed_and_persisted_in_history(): void
    {
        $migration = require dirname( __DIR__, 2 ) . '/Migrations/2026_09_04_000000_create_oxen_tables.php';
        $migration->up();

        $user = User::withoutEvents( fn (): User => User::query()->create( [
            'username' => 'oxen-failure-test',
            'email' => 'oxen-failure-test@example.com',
            'password' => bcrypt( 'password' ),
            'active' => true,
        ] ) );
        $setting = Setting::query()->firstOrCreate( ['provider' => 'openai'] );
        $setting->update( ['api_key' => 'sk-test-secret', 'assistant_enabled' => true, 'daily_message_limit' => 100] );
        $conversation = Conversation::query()->create( [
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'New conversation',
            'provider' => 'openai',
            'model' => 'gpt-test',
            'status' => 'active',
            'last_activity_at' => now(),
        ] );
        $conversation->messages()->create( [
            'role' => 'assistant',
            'content' => 'Action approved and completed: Existing action.',
            'metadata' => ['type' => 'info', 'action_event' => ['proposal_id' => 'existing-proposal', 'decision' => 'approved', 'tool' => 'update_settings']],
        ] );
        $request = Mockery::mock( MessageRequest::class );
        $request->shouldReceive( 'user' )->andReturn( $user );
        $request->shouldReceive( 'validated' )->with( 'message', '' )->andReturn( 'What happened?' );
        $request->shouldReceive( 'validated' )->with( 'route_name' )->andReturnNull();
        $request->shouldReceive( 'validated' )->with( 'entity_id' )->andReturnNull();
        $request->shouldReceive( 'file' )->with( 'attachments', [] )->andReturn( [] );
        $provider = Mockery::mock( OpenAIProvider::class );
        $provider->shouldReceive( 'respond' )
            ->once()
            ->withArgs( function ( Setting $setting, array $input ): bool {
                $this->assertFalse( collect( $input )->contains( 'content', 'Action approved and completed: Existing action.' ) );

                return true;
            } )
            ->andReturnUsing( function ( mixed ...$arguments ): never {
                $sendToolEvent = $arguments[6] ?? null;
                $this->assertIsCallable( $sendToolEvent );
                $sendToolEvent( 'tool.started', ['tool' => 'search_products', 'call_id' => 'call-live'] );
                $sendToolEvent( 'tool.completed', ['tool' => 'search_products', 'call_id' => 'call-live', 'summary' => 'One product found.'] );

                throw new OxenException( 'PROVIDER_UNAVAILABLE', 'The assistant provider is unavailable.', 503 );
            } );

        $response = app( OxenController::class )->message(
            $request,
            $conversation->public_id,
            $provider,
            Mockery::mock( ToolRegistry::class ),
            Mockery::mock( ActionProposalService::class ),
            app( ConversationContextBuilder::class ),
            app( ConversationAttachmentService::class ),
        );
        $testResponse = TestResponse::fromBaseResponse( $response );
        $testResponse->assertHeader( 'Content-Type', 'text/event-stream; charset=UTF-8' );
        $testResponse->assertHeader( 'Cache-Control', 'no-cache, no-transform, private' );
        $testResponse->assertHeader( 'X-Accel-Buffering', 'no' );
        $stream = $testResponse->streamedContent();

        $this->assertStringStartsWith( ': ' . str_repeat( ' ', 4096 ) . "\n\n", $stream );
        $this->assertStringContainsString( 'event: text.delta', $stream );
        $this->assertStringContainsString( 'event: message.accepted', $stream );
        $this->assertStringContainsString( 'event: tool.started', $stream );
        $this->assertStringContainsString( 'event: tool.completed', $stream );
        $this->assertStringContainsString( 'The assistant provider is unavailable.', $stream );
        $this->assertLessThan( strpos( $stream, 'event: text.delta' ), strpos( $stream, 'event: tool.started' ) );
        $this->assertLessThan( strpos( $stream, 'event: text.delta' ), strpos( $stream, 'event: tool.completed' ) );
        $this->assertDatabaseHas( 'nexopos_oxen_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'The assistant provider is unavailable.',
            'status' => 'failed',
        ] );
    }

    public function test_assistant_turn_advances_and_reuses_the_provider_response_checkpoint(): void
    {
        ( require dirname( __DIR__, 2 ) . '/Migrations/2026_09_04_000000_create_oxen_tables.php' )->up();
        ( require dirname( __DIR__, 2 ) . '/Migrations/2026_09_08_163014_add_provider_response_state_to_nexopos_oxen_conversations_table.php' )->up();

        $user = User::withoutEvents( fn (): User => User::query()->create( [
            'username' => 'oxen-response-state-test',
            'email' => 'oxen-response-state-test@example.com',
            'password' => bcrypt( 'password' ),
            'active' => true,
        ] ) );
        $setting = Setting::query()->firstOrCreate( ['provider' => 'openai'] );
        $setting->update( ['api_key' => 'sk-test-secret', 'model' => 'gpt-test', 'assistant_enabled' => true, 'daily_message_limit' => 100] );
        $conversation = Conversation::query()->create( [
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Existing response chain',
            'provider' => 'openai',
            'model' => 'gpt-test',
            'status' => 'active',
            'last_activity_at' => now(),
        ] );
        $checkpoint = $conversation->messages()->create( ['role' => 'assistant', 'content' => 'Prior provider answer.'] );
        $conversation->update( ['provider_response_id' => 'resp-prior', 'provider_response_message_id' => $checkpoint->id] );
        $request = Mockery::mock( MessageRequest::class );
        $request->shouldReceive( 'user' )->andReturn( $user );
        $request->shouldReceive( 'validated' )->with( 'message', '' )->andReturn( 'Continue from there.' );
        $request->shouldReceive( 'validated' )->with( 'route_name' )->andReturnNull();
        $request->shouldReceive( 'validated' )->with( 'entity_id' )->andReturnNull();
        $request->shouldReceive( 'file' )->with( 'attachments', [] )->andReturn( [] );
        $provider = Mockery::mock( OpenAIProvider::class );
        $provider->shouldReceive( 'respond' )
            ->once()
            ->withArgs( function ( mixed ...$arguments ): bool {
                $this->assertSame( 'resp-prior', $arguments[7] );
                $this->assertSame( 'Continue from there.', data_get( $arguments[8], '0.content' ) );
                $this->assertStringNotContainsString( 'Prior provider answer.', json_encode( $arguments[8], JSON_THROW_ON_ERROR ) );

                return true;
            } )
            ->andReturn( [
                'markdown' => 'Continued successfully.',
                'conversation_title' => null,
                'usage' => ['input_tokens' => 10, 'output_tokens' => 4],
                'actions' => [],
                'tool_events' => [],
                'response_id' => 'resp-next',
            ] );

        $response = app( OxenController::class )->message(
            $request,
            $conversation->public_id,
            $provider,
            Mockery::mock( ToolRegistry::class ),
            Mockery::mock( ActionProposalService::class ),
            app( ConversationContextBuilder::class ),
            app( ConversationAttachmentService::class ),
        );
        TestResponse::fromBaseResponse( $response )->streamedContent();

        $conversation->refresh();
        $this->assertSame( 'resp-next', $conversation->provider_response_id );
        $this->assertNotSame( $checkpoint->id, $conversation->provider_response_message_id );
        $this->assertSame( 'Continued successfully.', $conversation->messages()->findOrFail( $conversation->provider_response_message_id )->content );
    }

    public function test_expired_action_status_is_committed_and_replays_stably(): void
    {
        $tablesMigration = require dirname( __DIR__, 2 ) . '/Migrations/2026_09_04_000000_create_oxen_tables.php';
        $tablesMigration->up();
        $proposalMigration = require dirname( __DIR__, 2 ) . '/Migrations/2026_09_05_125028_create_oxen_action_proposals_table.php';
        $proposalMigration->up();

        $user = User::withoutEvents( fn (): User => User::query()->create( [
            'username' => 'oxen-expiry-test',
            'email' => 'oxen-expiry-test@example.com',
            'password' => bcrypt( 'password' ),
            'active' => true,
        ] ) );
        $conversation = Conversation::query()->create( [
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Expiry test',
            'provider' => 'openai',
            'model' => 'gpt-test',
            'status' => 'active',
            'last_activity_at' => now(),
        ] );
        $definition = app( ToolRegistry::class )->definition( 'update_settings' );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'definition' )->times( 3 )->with( 'update_settings' )->andReturn( $definition );
        $registry->shouldReceive( 'available' )->once()->with( $user, 'update_settings' )->andReturnTrue();
        $registry->shouldNotReceive( 'execute' );
        $service = new ActionProposalService( $registry, app( StoreClock::class ) );
        $proposal = $service->create( $user, $conversation, 'update_settings', ['settings' => ['ns_store_name' => 'AFCK']] );
        $message = Message::query()->create( [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Approve the store name change.',
            'metadata' => ['actions' => [$service->present( $proposal )]],
        ] );
        $proposal->update( ['message_id' => $message->id] );

        $this->assertTrue( $proposal->expires_at->between( now()->addMinutes( 119 ), now()->addMinutes( 121 ) ) );
        $proposal->update( ['expires_at' => now()->subSecond()] );

        foreach ( range( 1, 2 ) as $attempt ) {
            try {
                $service->execute( $user, $proposal->public_id );
                $this->fail( "Expired action attempt {$attempt} unexpectedly executed." );
            } catch ( OxenException $exception ) {
                $this->assertSame( 'EXPIRED', $exception->errorCode );
                $this->assertSame( 409, $exception->httpStatus );
            }
        }

        $this->assertSame( 'expired', $proposal->fresh()->status );

        $request = Request::create( '/api/oxen/conversations/' . $conversation->public_id );
        $request->setUserResolver( fn (): User => $user );
        $history = app( OxenController::class )->show( $request, $conversation->public_id, $service, app( ConversationAttachmentService::class ) )->getData( true );
        $this->assertSame( 'expired', data_get( $history, 'messages.0.metadata.actions.0.status' ) );
    }

    public function test_modules_can_register_contract_tools_and_duplicates_fail_closed(): void
    {
        $registry = new ToolRegistry(
            app( AuthorizeOxenOperation::class ),
            app( SafeWriteTools::class ),
            app( ProductManagementService::class ),
            app( CatalogCreationService::class ),
            app( ReportService::class ),
            app(),
        );

        $registry->registerTools( [ExampleOxenTool::class] );
        $this->assertContains( 'example_lookup', $registry->names() );

        $this->expectException( InvalidArgumentException::class );
        $registry->registerTools( [ExampleOxenTool::class] );
    }

    public function test_openai_provider_stores_response_state_enables_compaction_and_bounds_output(): void
    {
        Http::preventStrayRequests();
        Http::fake( ['api.openai.com/v1/responses' => Http::response( [
            'output' => [ ['content' => [ ['type' => 'output_text', 'text' => '{"markdown":"**Ready.**","conversation_title":"Ready Store Assistant"}'] ]] ],
            'usage' => ['input_tokens' => 4, 'output_tokens' => 2],
        ] )] );
        $setting = new Setting( ['api_key' => 'sk-test-secret', 'model' => 'gpt-test', 'output_token_limit' => 321] );
        $result = app( OpenAIProvider::class )->respond( $setting, [['role' => 'user', 'content' => 'Hello']] );
        $this->assertSame( '**Ready.**', $result['markdown'] );
        $this->assertSame( 'Ready Store Assistant', $result['conversation_title'] );
        Http::assertSent( fn ( $request ): bool => $request['store'] === true
            && $request['max_output_tokens'] === 321
            && data_get( $request->data(), 'context_management.0.type' ) === 'compaction'
            && data_get( $request->data(), 'context_management.0.compact_threshold' ) === 12000
            && $request['text']['format']['name'] === 'oxen_response'
            && $request->hasHeader( 'Authorization', 'Bearer sk-test-secret' ) );
    }

    public function test_new_proposals_execute_immediately_in_store_timezones_ahead_and_behind_utc(): void
    {
        $this->migrateOxenTables();

        foreach ( ['Pacific/Auckland', 'America/Los_Angeles'] as $timezone ) {
            $this->useStoreTime( $timezone );
            $user = $this->createOxenUser( Str::slug( $timezone ) );
            $conversation = $this->createConversation( $user, 'Timezone proposal test' );
            $definition = app( ToolRegistry::class )->definition( 'update_settings' );
            $registry = Mockery::mock( ToolRegistry::class );
            $registry->shouldReceive( 'definition' )->with( 'update_settings' )->andReturn( $definition );
            $registry->shouldReceive( 'available' )->twice()->with( $user, 'update_settings' )->andReturnTrue();
            $registry->shouldReceive( 'execute' )->once()->andReturn( ['ok' => true, 'data' => ['reference' => 'settings']] );
            $clock = new StoreClock;
            $service = new ActionProposalService( $registry, $clock );

            $proposal = $service->create( $user, $conversation, 'update_settings', ['settings' => ['ns_store_name' => 'Timezone Store']] );
            $expiresAt = $proposal->expires_at->copy();
            $message = Message::query()->create( [
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => 'Approve the store name change.',
                'metadata' => ['actions' => [$service->present( $proposal )]],
            ] );
            $service->attachToMessage( [$proposal->public_id], $message );
            $proposal->refresh();

            $this->assertSame( $timezone, $proposal->expires_at->getTimezone()->getName() );
            $this->assertEqualsWithDelta( 120, $clock->now()->diffInMinutes( $proposal->expires_at ), 1 );
            $this->assertTrue( $proposal->expires_at->equalTo( $expiresAt ) );
            $this->assertSame( $message->id, $proposal->message_id );
            $result = $service->execute( $user, $proposal->public_id );

            $this->assertSame( 'executed', $result['proposal']['status'] );
            $this->assertSame( 'executed', $proposal->fresh()->status );
            $this->assertDatabaseHas( 'nexopos_oxen_messages', [
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => 'Action approved and completed: Update store settings.',
            ] );
        }
    }

    public function test_rejected_actions_are_recorded_as_info_messages(): void
    {
        $this->migrateOxenTables();
        $user = $this->createOxenUser( 'reject-info' );
        $conversation = $this->createConversation( $user, 'Reject info test' );
        $definition = app( ToolRegistry::class )->definition( 'update_settings' );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'definition' )->with( 'update_settings' )->andReturn( $definition );
        $registry->shouldReceive( 'available' )->once()->with( $user, 'update_settings' )->andReturnTrue();
        $registry->shouldNotReceive( 'execute' );
        $service = new ActionProposalService( $registry, new StoreClock );
        $proposal = $service->create( $user, $conversation, 'update_settings', ['settings' => ['ns_store_name' => 'Rejected Store']] );

        $result = $service->reject( $user, $proposal->public_id );

        $this->assertSame( 'rejected', $result['status'] );
        $this->assertDatabaseHas( 'nexopos_oxen_messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Action rejected. No changes were made: Update store settings.',
        ] );
        $infoMessage = $conversation->messages()->latest( 'id' )->first();
        $this->assertSame( 'info', data_get( $infoMessage?->metadata, 'type' ) );
        $this->assertSame( 'rejected', data_get( $infoMessage?->metadata, 'action_event.decision' ) );

        $request = Request::create( '/api/oxen/conversations/' . $conversation->public_id );
        $request->setUserResolver( fn (): User => $user );
        $history = app( OxenController::class )->show( $request, $conversation->public_id, $service, app( ConversationAttachmentService::class ) )->getData( true );
        $this->assertSame( 'info', data_get( $history, 'messages.0.type' ) );
    }

    public function test_retry_creates_a_fresh_valid_proposal_without_executing_the_expired_one(): void
    {
        $this->migrateOxenTables();
        $this->useStoreTime( 'Asia/Tokyo' );
        $user = $this->createOxenUser( 'retry' );
        $conversation = $this->createConversation( $user, 'Retry proposal test' );
        $definition = app( ToolRegistry::class )->definition( 'update_settings' );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'definition' )->with( 'update_settings' )->andReturn( $definition );
        $registry->shouldReceive( 'available' )->twice()->with( $user, 'update_settings' )->andReturnTrue();
        $registry->shouldNotReceive( 'execute' );
        $clock = new StoreClock;
        $service = new ActionProposalService( $registry, $clock );
        $expired = $service->create( $user, $conversation, 'update_settings', ['settings' => ['ns_store_name' => 'Retry Store']] );
        $expired->update( ['expires_at' => $clock->now()->subSecond()] );

        $replacement = $service->retry( $user, $expired->public_id );

        $this->assertNotSame( $expired->public_id, $replacement['id'] );
        $this->assertSame( 'expired', $expired->fresh()->status );
        $this->assertSame( 'pending', $replacement['status'] );
        $this->assertTrue( $clock->normalize( $replacement['expires_at'] )->isFuture() );
        $this->assertNull( $expired->fresh()->executed_at );
    }

    public function test_execute_reauthorizes_a_pending_proposal(): void
    {
        $this->migrateOxenTables();
        $user = $this->createOxenUser( 'reauthorize' );
        $conversation = $this->createConversation( $user, 'Authorization proposal test' );
        $definition = app( ToolRegistry::class )->definition( 'update_settings' );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'definition' )->with( 'update_settings' )->andReturn( $definition );
        $registry->shouldReceive( 'available' )->twice()->with( $user, 'update_settings' )->andReturn( true, false );
        $registry->shouldNotReceive( 'execute' );
        $service = new ActionProposalService( $registry, new StoreClock );
        $proposal = $service->create( $user, $conversation, 'update_settings', ['settings' => ['ns_store_name' => 'Protected Store']] );

        try {
            $service->execute( $user, $proposal->public_id );
            $this->fail( 'A proposal executed after its permission was revoked.' );
        } catch ( OxenException $exception ) {
            $this->assertSame( 'FORBIDDEN', $exception->errorCode );
        }

        $this->assertSame( 'pending', $proposal->fresh()->status );
    }

    public function test_oxen_write_models_only_allow_safe_fields_for_mass_assignment(): void
    {
        $product = new Product;
        $category = new ProductCategory;

        $this->assertSame( ['name', 'description', 'status', 'category_id'], $product->getFillable() );
        $this->assertSame( ['name', 'parent_id', 'description'], $category->getFillable() );
        $this->assertInstanceOf( NexoPOSProduct::class, $product );
        $this->assertInstanceOf( NexoPOSProductCategory::class, $category );
        $this->assertSame( ( new NexoPOSProduct )->getTable(), $product->getTable() );
        $this->assertSame( ( new NexoPOSProductCategory )->getTable(), $category->getTable() );
        $this->assertSame( [], ( new NexoPOSProduct )->getFillable() );
        $this->assertSame( [], ( new NexoPOSProductCategory )->getFillable() );

        foreach ( ['id', 'author_id', 'uuid', 'sku', 'barcode', 'stock_management', 'accurate_tracking', 'created_at', 'updated_at'] as $field ) {
            $this->assertFalse( $product->isFillable( $field ), "Product field [{$field}] must remain protected." );
        }
        foreach ( ['id', 'author_id', 'uuid', 'displays_on_pos', 'scale_range_id', 'created_at', 'updated_at'] as $field ) {
            $this->assertFalse( $category->isFillable( $field ), "Category field [{$field}] must remain protected." );
        }

        $product->fill( ['name' => 'Safe product', 'description' => 'Safe description', 'status' => 'available', 'category_id' => 5] );
        $category->fill( ['name' => 'Safe category', 'parent_id' => 2, 'description' => 'Safe description'] );

        $this->assertSame( 'Safe product', $product->name );
        $this->assertSame( 'Safe category', $category->name );
    }

    public function test_openai_provider_receives_trusted_time_version_and_all_module_states(): void
    {
        $this->useStoreTime( 'Pacific/Auckland' );
        Http::preventStrayRequests();
        Http::fake( ['api.openai.com/v1/responses' => Http::response( [
            'output' => [['content' => [['type' => 'output_text', 'text' => '{"markdown":"Ready.","conversation_title":"Trusted Context Test"}']]]],
            'usage' => ['input_tokens' => 4, 'output_tokens' => 2],
        ] )] );
        $modulesService = Mockery::mock( ModulesService::class );
        $modulesService->shouldReceive( 'get' )->once()->withNoArgs()->andReturn( [
            'EnabledModule' => ['namespace' => 'EnabledModule', 'name' => 'Enabled Module', 'version' => '1.2.3', 'enabled' => true],
            'DisabledModule' => ['namespace' => 'DisabledModule', 'name' => 'Disabled Module', 'version' => '4.5.6', 'enabled' => false],
        ] );
        $context = new TrustedSystemContext( new StoreClock, $modulesService );
        $provider = new OpenAIProvider( $context );
        $setting = new Setting( ['api_key' => 'sk-test-secret', 'model' => 'gpt-test', 'output_token_limit' => 321] );

        $provider->respond( $setting, [['role' => 'user', 'content' => 'What system is this?']] );

        Http::assertSent( function ( $request ): bool {
            $message = collect( $request['input'] )->first( fn ( array $item ): bool => ( $item['role'] ?? null ) === 'developer' && str_starts_with( $item['content'] ?? '', 'Trusted NexoPOS system context' ) );
            $context = json_decode( Str::after( $message['content'] ?? '', ': ' ), true );

            return data_get( $context, 'current_datetime.timezone' ) === 'Pacific/Auckland'
                && data_get( $context, 'current_datetime.store' ) === '2026-09-06T22:00:00+12:00'
                && data_get( $context, 'current_datetime.utc' ) === '2026-09-06T10:00:00+00:00'
                && data_get( $context, 'nexopos_version' ) === config( 'nexopos.version' )
                && data_get( $context, 'modules.0.namespace' ) === 'DisabledModule'
                && data_get( $context, 'modules.0.enabled' ) === false
                && data_get( $context, 'modules.1.namespace' ) === 'EnabledModule'
                && data_get( $context, 'modules.1.enabled' ) === true;
        } );
    }

    public function test_assistant_repairs_false_confirmation_claim_and_creates_an_image_proposal(): void
    {
        Http::preventStrayRequests();
        Http::fakeSequence( 'api.openai.com/v1/responses' )
            ->push( ['output' => [['type' => 'function_call', 'name' => 'search_products', 'call_id' => 'call-search', 'arguments' => '{"search":"Bluetooth Earbuds"}']]] )
            ->push( ['output' => [['content' => [['type' => 'output_text', 'text' => '{"markdown":"I am ready to generate it. Please confirm to proceed.","conversation_title":"Create Earbuds Image"}']]]]] )
            ->push( ['output' => [['type' => 'function_call', 'name' => 'generate_product_image', 'call_id' => 'call-image', 'arguments' => '{"product_id":103,"prompt":"Bluetooth earbuds product image","idempotency_key":"image-proposal-key"}']]] )
            ->push( ['output' => [['content' => [['type' => 'output_text', 'text' => '{"markdown":"Review the proposed product image.","conversation_title":"Create Earbuds Image"}']]]]] );

        $searchDefinition = app( ToolRegistry::class )->definition( 'search_products' );
        $imageDefinition = app( ToolRegistry::class )->definition( 'generate_product_image' );
        $registry = Mockery::mock( ToolRegistry::class );
        $registry->shouldReceive( 'availableDefinitions' )->once()->andReturn( [
            'search_products' => $searchDefinition,
            'generate_product_image' => $imageDefinition,
        ] );
        $registry->shouldReceive( 'definition' )->with( 'search_products' )->once()->andReturn( $searchDefinition );
        $registry->shouldReceive( 'definition' )->with( 'generate_product_image' )->once()->andReturn( $imageDefinition );
        $registry->shouldReceive( 'execute' )->once()->andReturn( ['ok' => true, 'data' => ['products' => [['id' => 103, 'name' => 'Bluetooth Earbuds']]]] );

        $proposal = new ActionProposal( ['public_id' => 'proposal-1'] );
        $proposals = Mockery::mock( ActionProposalService::class );
        $proposals->shouldReceive( 'create' )->once()->andReturn( $proposal );
        $proposals->shouldReceive( 'present' )->once()->andReturn( ['id' => 'proposal-1', 'tool' => 'generate_product_image', 'title' => 'Generate product image', 'risk' => 'write', 'requires_confirmation' => true, 'status' => 'pending'] );

        $setting = new Setting( ['api_key' => 'sk-test-secret', 'model' => 'gpt-test', 'output_token_limit' => 321] );
        $user = Mockery::mock( User::class );
        $conversation = Mockery::mock( Conversation::class );
        $liveEvents = [];
        $result = app( OpenAIProvider::class )->respond(
            $setting,
            [['role' => 'user', 'content' => 'Create a product image for Bluetooth Earbuds']],
            $user,
            $registry,
            $conversation,
            $proposals,
            function ( string $event, array $data ) use ( &$liveEvents ): void {
                $liveEvents[] = compact( 'event', 'data' );
            },
        );

        $this->assertSame( 'proposal-1', $result['actions'][0]['id'] );
        $this->assertSame( 'generate_product_image', $result['actions'][0]['tool'] );
        $this->assertTrue( $result['actions'][0]['requires_confirmation'] );
        $this->assertSame( ['tool.completed', 'tool.completed'], array_column( $result['tool_events'], 'event' ) );
        $this->assertSame( ['tool.started', 'tool.completed', 'tool.started', 'tool.completed'], array_column( $liveEvents, 'event' ) );
        Http::assertSentCount( 4 );
        Http::assertSent( fn ( $request ): bool => collect( $request['input'] )->contains(
            fn ( array $item ): bool => ( $item['role'] ?? null ) === 'developer' && str_contains( $item['content'], 'no action proposal exists' ),
        ) );
    }

    public function test_api_key_is_encrypted_and_hidden(): void
    {
        $setting = new Setting( ['api_key' => 'sk-sensitive'] );
        $this->assertNotSame( 'sk-sensitive', $setting->getAttributes()['api_key'] );
        $this->assertArrayNotHasKey( 'api_key', $setting->toArray() );
    }

    private function migrateOxenTables(): void
    {
        $tablesMigration = require dirname( __DIR__, 2 ) . '/Migrations/2026_09_04_000000_create_oxen_tables.php';
        $tablesMigration->up();
        $proposalMigration = require dirname( __DIR__, 2 ) . '/Migrations/2026_09_05_125028_create_oxen_action_proposals_table.php';
        $proposalMigration->up();
        $expiresAtMigration = require dirname( __DIR__, 2 ) . '/Migrations/2026_09_08_012559_modify_expires_at_on_nexopos_oxen_action_proposals_table.php';
        $expiresAtMigration->up();
    }

    private function createOxenUser( string $suffix ): User
    {
        return User::withoutEvents( fn (): User => User::query()->create( [
            'username' => 'oxen-' . $suffix . '-' . Str::lower( Str::random( 8 ) ),
            'email' => 'oxen-' . $suffix . '-' . Str::lower( Str::random( 8 ) ) . '@example.com',
            'password' => bcrypt( 'password' ),
            'active' => true,
        ] ) );
    }

    private function createConversation( User $user, string $title ): Conversation
    {
        return Conversation::query()->create( [
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => $title,
            'provider' => 'openai',
            'model' => 'gpt-test',
            'status' => 'active',
            'last_activity_at' => ( new StoreClock )->now(),
        ] );
    }

    private function useStoreTime( string $timezone ): void
    {
        $this->originalDateService ??= ns()->date;
        $this->originalApplicationTimezone ??= (string) config( 'app.timezone' );
        $this->originalPhpTimezone ??= date_default_timezone_get();

        Carbon::setTestNow( Carbon::parse( '2026-09-06T10:00:00+00:00' ) );
        config( ['app.timezone' => $timezone] );
        date_default_timezone_set( $timezone );
        ns()->date = new DateService( 'now', $timezone );
    }
}

class ExampleOxenTool implements OxenToolContract
{
    public function definition(): OxenToolDefinition
    {
        return new OxenToolDefinition(
            name: 'example_lookup',
            title: 'Example lookup',
            description: 'Test lookup.',
            category: 'Tests',
            sourceModule: 'TestModule',
            inputSchema: ['type' => 'object', 'properties' => [], 'additionalProperties' => false],
            outputSchema: ['type' => 'object', 'properties' => []],
            rules: [],
            ability: 'oxen:read',
            permissions: ['ns.oxen.use'],
        );
    }

    public function execute( OxenExecutionContext $context, array $input ): OxenToolResult
    {
        return new OxenToolResult( ['ok' => true] );
    }
}
