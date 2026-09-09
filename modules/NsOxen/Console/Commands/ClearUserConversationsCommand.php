<?php

namespace Modules\NsOxen\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\NsOxen\Models\ActionProposal;
use Modules\NsOxen\Models\Conversation;
use Modules\NsOxen\Models\Message;
use Modules\NsOxen\Services\ConversationAttachmentService;

class ClearUserConversationsCommand extends Command
{
    protected $signature = 'ns:oxen:clear-user-conversations
        {user : Exact user ID, email, or username}
        {--force : Skip the confirmation prompt}';

    protected $description = 'Erase all Oxen conversations for a specific user.';

    public function handle( ConversationAttachmentService $attachments ): int
    {
        $identifier = trim( (string) $this->argument( 'user' ) );
        $user = $this->resolveUser( $identifier );

        if ( ! $user instanceof User ) {
            $this->error( sprintf( __m( 'Unable to locate a unique user matching "%s".', 'NsOxen' ), $identifier ) );

            return self::FAILURE;
        }

        $conversationIds = Conversation::query()
            ->where( 'user_id', $user->id )
            ->pluck( 'id' );
        $conversationPublicIds = Conversation::query()
            ->whereIn( 'id', $conversationIds )
            ->pluck( 'public_id' );

        if ( $conversationIds->isEmpty() ) {
            $this->info( sprintf( __m( 'No Oxen conversations were found for %s.', 'NsOxen' ), $this->userLabel( $user ) ) );

            return self::SUCCESS;
        }

        $conversationCount = $conversationIds->count();
        $messageCount = Message::query()->whereIn( 'conversation_id', $conversationIds )->count();

        if ( ! $this->option( 'force' ) && ! $this->confirm( $this->confirmationQuestion( $user, $conversationCount, $messageCount ) ) ) {
            $this->warn( __m( 'No Oxen conversations were erased.', 'NsOxen' ) );

            return self::SUCCESS;
        }

        DB::transaction( function () use ( $conversationIds ): void {
            ActionProposal::query()->whereIn( 'conversation_id', $conversationIds )->delete();
            Message::query()->whereIn( 'conversation_id', $conversationIds )->delete();
            Conversation::query()->whereIn( 'id', $conversationIds )->delete();
        } );
        $conversationPublicIds->each( fn ( string $publicId ) => $attachments->deleteConversation( $publicId ) );

        $this->info( sprintf(
            __m( 'Erased %d Oxen conversation(s) and %d message(s) for %s.', 'NsOxen' ),
            $conversationCount,
            $messageCount,
            $this->userLabel( $user ),
        ) );
        $this->line( __m( 'Oxen audit operations and usage records were retained.', 'NsOxen' ) );

        return self::SUCCESS;
    }

    private function resolveUser( string $identifier ): ?User
    {
        if ( $identifier === '' ) {
            return null;
        }

        if ( ctype_digit( $identifier ) ) {
            return User::query()->find( (int) $identifier );
        }

        $users = User::query()
            ->where( function ( $query ) use ( $identifier ): void {
                $query->where( 'email', $identifier )
                    ->orWhere( 'username', $identifier );
            } )
            ->limit( 2 )
            ->get();

        return $users->count() === 1 ? $users->first() : null;
    }

    private function confirmationQuestion( User $user, int $conversationCount, int $messageCount ): string
    {
        return sprintf(
            __m( 'Erase %d Oxen conversation(s) and %d message(s) for %s? This cannot be undone.', 'NsOxen' ),
            $conversationCount,
            $messageCount,
            $this->userLabel( $user ),
        );
    }

    private function userLabel( User $user ): string
    {
        return sprintf( '%s (ID: %d, %s)', $user->username, $user->id, $user->email );
    }
}
