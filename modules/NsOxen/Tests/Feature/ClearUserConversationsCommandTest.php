<?php

namespace Modules\NsOxen\Tests\Feature;

use App\Models\User;
use Illuminate\Console\Application as ArtisanApplication;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Modules\NsOxen\Console\Commands\ClearUserConversationsCommand;
use Modules\NsOxen\Models\ActionProposal;
use Modules\NsOxen\Models\Conversation;
use Modules\NsOxen\Models\Message;
use Modules\NsOxen\Models\Operation;
use Modules\NsOxen\Models\Usage;
use Tests\TestCase;

class ClearUserConversationsCommandTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $tablesMigration = require dirname( __DIR__, 2 ) . '/Migrations/2026_09_04_000000_create_oxen_tables.php';
        $tablesMigration->up();
        $proposalMigration = require dirname( __DIR__, 2 ) . '/Migrations/2026_09_05_125028_create_oxen_action_proposals_table.php';
        $proposalMigration->up();

        ArtisanApplication::starting( function ( ArtisanApplication $artisan ): void {
            $artisan->resolve( ClearUserConversationsCommand::class );
        } );
    }

    public function test_force_erases_only_the_selected_users_conversation_history(): void
    {
        $target = $this->createUser( 'target' );
        $other = $this->createUser( 'other' );
        $targetConversation = $this->createConversation( $target );
        $otherConversation = $this->createConversation( $other );
        $targetMessage = $this->createMessage( $targetConversation );
        $otherMessage = $this->createMessage( $otherConversation );
        $targetProposal = $this->createProposal( $target, $targetConversation, $targetMessage );
        $otherProposal = $this->createProposal( $other, $otherConversation, $otherMessage );
        $operation = Operation::query()->create( [
            'correlation_id' => (string) Str::uuid(),
            'user_id' => $target->id,
            'tool' => 'search_products',
            'redacted_input' => [],
            'input_hash' => hash( 'sha256', 'operation' ),
            'status' => 'completed',
            'duration_ms' => 10,
        ] );
        $usage = Usage::query()->create( [
            'user_id' => $target->id,
            'usage_date' => now()->toDateString(),
            'messages' => 1,
            'input_tokens' => 2,
            'output_tokens' => 3,
        ] );

        $this->artisan( 'ns:oxen:clear-user-conversations', [
            'user' => $target->email,
            '--force' => true,
        ] )
            ->expectsOutputToContain( 'Erased 1 Oxen conversation(s) and 1 message(s)' )
            ->expectsOutputToContain( 'Oxen audit operations and usage records were retained.' )
            ->assertSuccessful();

        $this->assertDatabaseMissing( 'nexopos_oxen_conversations', ['id' => $targetConversation->id] );
        $this->assertDatabaseMissing( 'nexopos_oxen_messages', ['id' => $targetMessage->id] );
        $this->assertDatabaseMissing( 'nexopos_oxen_action_proposals', ['id' => $targetProposal->id] );
        $this->assertDatabaseHas( 'nexopos_oxen_conversations', ['id' => $otherConversation->id] );
        $this->assertDatabaseHas( 'nexopos_oxen_messages', ['id' => $otherMessage->id] );
        $this->assertDatabaseHas( 'nexopos_oxen_action_proposals', ['id' => $otherProposal->id] );
        $this->assertDatabaseHas( 'nexopos_oxen_operations', ['id' => $operation->id] );
        $this->assertDatabaseHas( 'nexopos_oxen_usage', ['id' => $usage->id] );
    }

    public function test_declining_confirmation_preserves_the_users_history(): void
    {
        $user = $this->createUser( 'decline' );
        $conversation = $this->createConversation( $user );
        $message = $this->createMessage( $conversation );
        $question = sprintf(
            'Erase 1 Oxen conversation(s) and 1 message(s) for %s (ID: %d, %s)? This cannot be undone.',
            $user->username,
            $user->id,
            $user->email,
        );

        $this->artisan( 'ns:oxen:clear-user-conversations', ['user' => $user->username] )
            ->expectsConfirmation( $question, 'no' )
            ->expectsOutputToContain( 'No Oxen conversations were erased.' )
            ->assertSuccessful();

        $this->assertDatabaseHas( 'nexopos_oxen_conversations', ['id' => $conversation->id] );
        $this->assertDatabaseHas( 'nexopos_oxen_messages', ['id' => $message->id] );
    }

    public function test_unknown_user_returns_a_failure_exit_code(): void
    {
        $this->artisan( 'ns:oxen:clear-user-conversations', ['user' => 'missing@example.com'] )
            ->expectsOutputToContain( 'Unable to locate a unique user matching "missing@example.com".' )
            ->assertFailed();
    }

    public function test_user_id_without_conversations_returns_success(): void
    {
        $user = $this->createUser( 'empty' );

        $this->artisan( 'ns:oxen:clear-user-conversations', ['user' => (string) $user->id] )
            ->expectsOutputToContain( 'No Oxen conversations were found' )
            ->assertSuccessful();
    }

    private function createUser( string $suffix ): User
    {
        return User::withoutEvents( fn (): User => User::query()->create( [
            'username' => 'oxen-command-' . $suffix . '-' . Str::lower( Str::random( 8 ) ),
            'email' => 'oxen-command-' . $suffix . '-' . Str::lower( Str::random( 8 ) ) . '@example.com',
            'password' => bcrypt( 'password' ),
            'active' => true,
        ] ) );
    }

    private function createConversation( User $user ): Conversation
    {
        return Conversation::query()->create( [
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Command test conversation',
            'provider' => 'openai',
            'model' => 'gpt-test',
            'status' => 'active',
            'last_activity_at' => now(),
        ] );
    }

    private function createMessage( Conversation $conversation ): Message
    {
        return Message::query()->create( [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Please find a product.',
            'status' => 'completed',
        ] );
    }

    private function createProposal( User $user, Conversation $conversation, Message $message ): ActionProposal
    {
        return ActionProposal::query()->create( [
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'conversation_id' => $conversation->id,
            'message_id' => $message->id,
            'tool' => 'update_product',
            'payload' => ['product_id' => 1, 'name' => 'Updated'],
            'input_hash' => hash( 'sha256', 'proposal-' . $user->id ),
            'risk' => 'write',
            'requires_confirmation' => true,
            'status' => 'pending',
            'expires_at' => now()->addMinutes( 10 ),
            'idempotency_key' => (string) Str::uuid(),
        ] );
    }
}
