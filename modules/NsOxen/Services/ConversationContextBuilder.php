<?php

namespace Modules\NsOxen\Services;

use Illuminate\Support\Str;
use Modules\NsOxen\Models\Conversation;
use Modules\NsOxen\Models\Message;

class ConversationContextBuilder
{
    private const CHARACTERS_PER_TOKEN = 4;

    public function __construct( private readonly ConversationAttachmentService $attachments ) {}

    /**
     * @return list<array{role: string, content: mixed}>
     */
    public function build( Conversation $conversation, Message $currentMessage, int $tokenLimit ): array
    {
        return $this->buildAfterMessage( $conversation, $currentMessage, $tokenLimit );
    }

    /**
     * Build only local events that occurred after the provider checkpoint.
     *
     * @return list<array{role: string, content: mixed}>
     */
    public function buildContinuation( Conversation $conversation, Message $currentMessage, int $tokenLimit, int $afterMessageId ): array
    {
        return $this->buildAfterMessage( $conversation, $currentMessage, $tokenLimit, $afterMessageId );
    }

    /**
     * @return list<array{role: string, content: mixed}>
     */
    private function buildAfterMessage( Conversation $conversation, Message $currentMessage, int $tokenLimit, ?int $afterMessageId = null ): array
    {
        $characterLimit = max( 1, $tokenLimit ) * self::CHARACTERS_PER_TOKEN;
        $currentContent = (string) $currentMessage->content;
        $boundedCurrentContent = mb_strlen( $currentContent ) <= $characterLimit
            ? $currentContent
            : Str::limit( $currentContent, $characterLimit, '' );
        $currentInput = ['role' => 'user', 'content' => $this->attachments->content( $currentMessage, $boundedCurrentContent )];
        $remaining = max( 0, $characterLimit - mb_strlen( $boundedCurrentContent ) - $this->attachments->estimatedCharacterCost( $currentMessage ) );
        $selected = [];

        $pageContext = $this->latestPageContext( $conversation, $currentMessage->id );
        if ( $pageContext !== null ) {
            $candidate = [
                'role' => 'developer',
                'content' => 'Trusted current page context (validated by the application): ' . json_encode( $pageContext, JSON_THROW_ON_ERROR ),
            ];
            $this->prependWhenItFits( $selected, $candidate, $remaining );
        }

        Message::query()
            ->with( 'conversation:id,public_id,user_id' )
            ->where( 'conversation_id', $conversation->id )
            ->where( 'status', 'completed' )
            ->where( 'id', '<', $currentMessage->id )
            ->when( $afterMessageId !== null, fn ( $query ) => $query->where( 'id', '>', $afterMessageId ) )
            ->orderByDesc( 'id' )
            ->get( ['id', 'role', 'content', 'metadata'] )
            ->each( function ( Message $message ) use ( &$selected, &$remaining ): void {
                $candidate = $this->messageInput( $message );

                if ( $candidate !== null ) {
                    $attachmentCost = $message->role === 'user' ? $this->attachments->estimatedCharacterCost( $message ) : 0;
                    $this->prependWhenItFits( $selected, $candidate, $remaining, $attachmentCost );
                }
            } );

        return [...$selected, $currentInput];
    }

    /** @return array{route_name?: string, entity_id?: int}|null */
    private function latestPageContext( Conversation $conversation, int $currentMessageId ): ?array
    {
        $message = Message::query()
            ->where( 'conversation_id', $conversation->id )
            ->where( 'status', 'completed' )
            ->where( 'id', '<=', $currentMessageId )
            ->where( 'role', 'user' )
            ->orderByDesc( 'id' )
            ->get( ['metadata'] )
            ->first( fn ( Message $message ): bool => is_string( data_get( $message->metadata, 'route_name' ) ) || is_int( data_get( $message->metadata, 'entity_id' ) ) );

        if ( ! $message ) {
            return null;
        }

        $context = [];
        $routeName = data_get( $message->metadata, 'route_name' );
        $entityId = data_get( $message->metadata, 'entity_id' );

        if ( is_string( $routeName ) && $routeName !== '' ) {
            $context['route_name'] = $routeName;
        }
        if ( is_int( $entityId ) ) {
            $context['entity_id'] = $entityId;
        }

        return $context !== [] ? $context : null;
    }

    /** @return array{role: string, content: mixed}|null */
    private function messageInput( Message $message ): ?array
    {
        $actionEvent = data_get( $message->metadata, 'action_event' );

        if ( is_array( $actionEvent ) ) {
            $decision = $actionEvent['decision'] ?? null;
            if ( ! in_array( $decision, ['approved', 'rejected'], true ) ) {
                return null;
            }

            return [
                'role' => 'developer',
                'content' => 'Trusted action outcome: ' . json_encode( [
                    'decision' => $decision,
                    'tool' => is_string( $actionEvent['tool'] ?? null ) ? $actionEvent['tool'] : null,
                    'proposal_id' => is_string( $actionEvent['proposal_id'] ?? null ) ? $actionEvent['proposal_id'] : null,
                ], JSON_THROW_ON_ERROR ),
            ];
        }

        if ( ! in_array( $message->role, ['user', 'assistant'], true ) || ! is_string( $message->content ) ) {
            return null;
        }

        return [
            'role' => $message->role,
            'content' => $message->role === 'user'
                ? $this->attachments->content( $message, $message->content )
                : $message->content,
        ];
    }

    /**
     * @param list<array{role: string, content: string}> $selected
     * @param array{role: string, content: mixed} $candidate
     */
    private function prependWhenItFits( array &$selected, array $candidate, int &$remaining, int $additionalCost = 0 ): void
    {
        $length = is_string( $candidate['content'] )
            ? mb_strlen( $candidate['content'] )
            : collect( $candidate['content'] )->sum( fn ( mixed $part ): int => is_array( $part ) && isset( $part['text'] ) ? mb_strlen( (string) $part['text'] ) : 0 );
        $length += $additionalCost;
        if ( $length <= $remaining ) {
            array_unshift( $selected, $candidate );
            $remaining -= $length;
        }
    }
}
