<?php

namespace Modules\NsOxen\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\NsOxen\Models\ActionProposal;
use Modules\NsOxen\Models\Conversation;
use Modules\NsOxen\Models\Message;

class ActionProposalService
{
    private const APPROVAL_TTL_MINUTES = 120;

    public function __construct(
        private readonly ToolRegistry $registry,
        private readonly StoreClock $clock,
    ) {}

    /** @param array<string, mixed> $input */
    public function create( User $user, Conversation $conversation, string $tool, array $input ): ActionProposal
    {
        $definition = $this->registry->definition( $tool )
            ?? throw new OxenException( 'NOT_FOUND', __m( 'Unknown Oxen tool.', 'NsOxen' ), 404 );

        if ( ! $definition->isWrite() || ! $this->registry->available( $user, $tool ) ) {
            throw new OxenException( 'FORBIDDEN', __m( 'This action is not available.', 'NsOxen' ), 403 );
        }

        if ( array_key_exists( 'store_id', $input ) ) {
            throw ValidationException::withMessages( ['store_id' => __m( 'Store context cannot be supplied by the assistant.', 'NsOxen' )] );
        }

        OxenInputValidator::ensureShape( $input, $definition->inputSchema );
        $validated = validator( $input, collect( $definition->rules )->except( 'idempotency_key' )->all() )->validate();
        if ( in_array( $tool, [
            'import_products', 'update_unit_group', 'update_unit', 'update_tax_group',
            'update_tax', 'update_coupon', 'update_customer_group',
        ], true ) ) {
            $this->registry->preflightProposal( $tool, $validated );
        }
        ksort( $validated );
        $idempotencyKey = (string) Str::uuid();

        $now = $this->clock->now();

        return ActionProposal::query()->create( [
            'public_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'store_id' => $this->storeId(),
            'conversation_id' => $conversation->id,
            'tool' => $tool,
            'payload' => $validated,
            'input_hash' => hash( 'sha256', json_encode( $validated, JSON_THROW_ON_ERROR ) ),
            'risk' => $definition->risk,
            'requires_confirmation' => $definition->requiresConfirmation,
            'status' => 'pending',
            'expires_at' => $now->copy()->addMinutes( self::APPROVAL_TTL_MINUTES ),
            'idempotency_key' => $idempotencyKey,
        ] );
    }

    /** @return array<string, mixed> */
    public function execute( User $user, string $publicId ): array
    {
        $tool = ActionProposal::query()->where( 'public_id', $publicId )->where( 'user_id', $user->id )->value( 'tool' );
        if ( in_array( $tool, ['generate_product_image', 'generate_store_logo'], true ) ) {
            return $this->executeGeneratedImage( $user, $publicId );
        }

        $now = $this->clock->now();
        $outcome = DB::transaction( function () use ( $user, $publicId, $now ): array|OxenException {
            $proposal = ActionProposal::query()
                ->where( 'public_id', $publicId )
                ->where( 'user_id', $user->id )
                ->lockForUpdate()
                ->firstOrFail();

            if ( $proposal->status === 'executed' ) {
                throw new OxenException( 'ALREADY_EXECUTED', __m( 'This action has already been executed.', 'NsOxen' ), 409 );
            }
            if ( $proposal->status === 'expired' ) {
                return new OxenException( 'EXPIRED', __m( 'This action has expired.', 'NsOxen' ), 409 );
            }
            if ( $proposal->status !== 'pending' ) {
                throw new OxenException( 'NOT_AVAILABLE', __m( 'This action is no longer available.', 'NsOxen' ), 409 );
            }
            if ( $this->clock->normalize( $proposal->expires_at )->lessThanOrEqualTo( $now ) ) {
                $proposal->update( ['status' => 'expired'] );

                return new OxenException( 'EXPIRED', __m( 'This action has expired.', 'NsOxen' ), 409 );
            }
            if ( $proposal->store_id !== $this->storeId() ) {
                throw new OxenException( 'STORE_MISMATCH', __m( 'This action belongs to a different store.', 'NsOxen' ), 409 );
            }
            if ( ! $this->registry->available( $user, $proposal->tool ) ) {
                throw new OxenException( 'FORBIDDEN', __m( 'You are no longer allowed to execute this action.', 'NsOxen' ), 403 );
            }

            $payload = [...$proposal->payload, 'idempotency_key' => $proposal->idempotency_key];
            if ( hash( 'sha256', json_encode( $proposal->payload, JSON_THROW_ON_ERROR ) ) !== $proposal->input_hash ) {
                throw new OxenException( 'CONFLICT', __m( 'The proposed action payload is invalid.', 'NsOxen' ), 409 );
            }

            $result = $this->registry->execute( $user, $proposal->tool, $payload, $proposal->conversation );
            $presented = $this->present( $proposal->fresh() );
            $proposal->update( [
                'status' => 'executed',
                'executed_at' => $this->clock->now(),
                'result_reference' => data_get( $result, 'data.reference' ),
            ] );
            $proposal->conversation->messages()->create( [
                'role' => 'assistant',
                'content' => __m( 'Action approved and completed:', 'NsOxen' ) . ' ' . $presented['title'] . '.',
                'metadata' => ['type' => 'info', 'action_event' => ['proposal_id' => $proposal->public_id, 'decision' => 'approved', 'tool' => $proposal->tool]],
                'status' => 'completed',
            ] );

            return ['proposal' => $this->present( $proposal->fresh() ), 'result' => $result];
        }, 3 );

        if ( $outcome instanceof OxenException ) {
            throw $outcome;
        }

        return $outcome;
    }

    /** @param list<string> $publicIds */
    public function attachToMessage( array $publicIds, Message $message ): void
    {
        ActionProposal::query()
            ->where( 'conversation_id', $message->conversation_id )
            ->whereIn( 'public_id', $publicIds )
            ->update( [
                'message_id' => $message->id,
                'expires_at' => DB::raw( 'expires_at' ),
            ] );
    }

    /** @return array<string, mixed> */
    private function executeGeneratedImage( User $user, string $publicId ): array
    {
        $proposal = DB::transaction( function () use ( $user, $publicId ): ActionProposal|OxenException {
            $proposal = ActionProposal::query()
                ->where( 'public_id', $publicId )
                ->where( 'user_id', $user->id )
                ->lockForUpdate()
                ->firstOrFail();

            if ( $proposal->status === 'executed' ) {
                throw new OxenException( 'ALREADY_EXECUTED', __m( 'This action has already been executed.', 'NsOxen' ), 409 );
            }
            if ( $proposal->status !== 'pending' ) {
                throw new OxenException( 'NOT_AVAILABLE', __m( 'This action is no longer available.', 'NsOxen' ), 409 );
            }
            if ( $this->clock->normalize( $proposal->expires_at )->lessThanOrEqualTo( $this->clock->now() ) ) {
                $proposal->update( ['status' => 'expired'] );

                return new OxenException( 'EXPIRED', __m( 'This action has expired.', 'NsOxen' ), 409 );
            }
            if ( $proposal->store_id !== $this->storeId() ) {
                throw new OxenException( 'STORE_MISMATCH', __m( 'This action belongs to a different store.', 'NsOxen' ), 409 );
            }
            if ( ! $this->registry->available( $user, $proposal->tool ) ) {
                throw new OxenException( 'FORBIDDEN', __m( 'You are no longer allowed to execute this action.', 'NsOxen' ), 403 );
            }
            if ( hash( 'sha256', json_encode( $proposal->payload, JSON_THROW_ON_ERROR ) ) !== $proposal->input_hash ) {
                throw new OxenException( 'CONFLICT', __m( 'The proposed action payload is invalid.', 'NsOxen' ), 409 );
            }

            $proposal->update( ['status' => 'executing'] );

            return $proposal->fresh();
        }, 3 );

        if ( $proposal instanceof OxenException ) {
            throw $proposal;
        }

        try {
            $payload = [...$proposal->payload, 'idempotency_key' => $proposal->idempotency_key];
            $result = $this->registry->execute( $user, $proposal->tool, $payload, $proposal->conversation );
        } catch ( \Throwable $exception ) {
            ActionProposal::query()->whereKey( $proposal->id )->where( 'status', 'executing' )->update( ['status' => 'pending'] );
            throw $exception;
        }

        return DB::transaction( function () use ( $proposal, $result ): array {
            $lockedProposal = ActionProposal::query()->whereKey( $proposal->id )->lockForUpdate()->firstOrFail();
            if ( $lockedProposal->status !== 'executing' ) {
                throw new OxenException( 'CONFLICT', __m( 'The action state changed while the image was generated.', 'NsOxen' ), 409 );
            }

            $lockedProposal->update( [
                'status' => 'executed',
                'executed_at' => $this->clock->now(),
                'result_reference' => (string) ( data_get( $result, 'data.gallery_id' ) ?? data_get( $result, 'data.square_media_id' ) ),
            ] );
            $presented = $this->present( $lockedProposal->fresh() );
            $lockedProposal->conversation->messages()->create( [
                'role' => 'assistant',
                'content' => __m( 'Action approved and completed:', 'NsOxen' ) . ' ' . $presented['title'] . '.',
                'metadata' => ['type' => 'info', 'action_event' => ['proposal_id' => $lockedProposal->public_id, 'decision' => 'approved', 'tool' => $lockedProposal->tool]],
                'status' => 'completed',
            ] );

            return ['proposal' => $presented, 'result' => $result];
        }, 3 );
    }

    /** @return array<string, mixed> */
    public function reject( User $user, string $publicId ): array
    {
        $now = $this->clock->now();
        $outcome = DB::transaction( function () use ( $user, $publicId, $now ): array|OxenException {
            $proposal = ActionProposal::query()->where( 'public_id', $publicId )->where( 'user_id', $user->id )->lockForUpdate()->firstOrFail();
            if ( $proposal->status === 'expired' ) {
                return new OxenException( 'EXPIRED', __m( 'This action has expired.', 'NsOxen' ), 409 );
            }
            if ( $proposal->status !== 'pending' ) {
                throw new OxenException( 'NOT_AVAILABLE', __m( 'This action is no longer available.', 'NsOxen' ), 409 );
            }
            if ( $this->clock->normalize( $proposal->expires_at )->lessThanOrEqualTo( $now ) ) {
                $proposal->update( ['status' => 'expired'] );

                return new OxenException( 'EXPIRED', __m( 'This action has expired.', 'NsOxen' ), 409 );
            }
            $proposal->update( ['status' => 'rejected', 'rejected_at' => $this->clock->now()] );
            $presented = $this->present( $proposal->fresh() );
            $proposal->conversation->messages()->create( [
                'role' => 'assistant',
                'content' => __m( 'Action rejected. No changes were made:', 'NsOxen' ) . ' ' . $presented['title'] . '.',
                'metadata' => ['type' => 'info', 'action_event' => ['proposal_id' => $proposal->public_id, 'decision' => 'rejected', 'tool' => $proposal->tool]],
                'status' => 'completed',
            ] );

            return $presented;
        }, 3 );

        if ( $outcome instanceof OxenException ) {
            throw $outcome;
        }

        return $outcome;
    }

    /** @return array<string, mixed> */
    public function retry( User $user, string $publicId ): array
    {
        $proposal = ActionProposal::query()->where( 'public_id', $publicId )->where( 'user_id', $user->id )->firstOrFail();
        $now = $this->clock->now();
        if ( $proposal->status === 'pending' && $this->clock->normalize( $proposal->expires_at )->lessThanOrEqualTo( $now ) ) {
            $proposal->update( ['status' => 'expired'] );
        }
        if ( $proposal->status !== 'expired' ) {
            throw new OxenException( 'NOT_AVAILABLE', __m( 'Only expired actions can be retried.', 'NsOxen' ), 409 );
        }
        $conversation = $proposal->conversation;
        if ( ! $conversation || $conversation->user_id !== $user->id || $proposal->store_id !== $this->storeId() ) {
            throw new OxenException( 'STORE_MISMATCH', __m( 'This action belongs to a different store.', 'NsOxen' ), 409 );
        }
        $replacement = $this->create( $user, $conversation, $proposal->tool, $proposal->payload );
        if ( $proposal->message_id ) {
            $message = Message::query()->find( $proposal->message_id );
            if ( $message ) {
                $this->attachToMessage( [$replacement->public_id], $message );
                $metadata = $message->metadata ?? [];
                $metadata['actions'] = collect( $metadata['actions'] ?? [] )->map( fn ( array $action ): array => ( $action['id'] ?? null ) === $proposal->public_id ? $this->present( $replacement ) : $action )->all();
                $message->update( ['metadata' => $metadata] );
            }
        }

        return $this->present( $replacement );
    }

    /** @return array<string, mixed> */
    public function present( ActionProposal $proposal ): array
    {
        $definition = $this->registry->definition( $proposal->tool );
        $now = $this->clock->now();
        $status = $proposal->status === 'pending' && $this->clock->normalize( $proposal->expires_at )->lessThanOrEqualTo( $now )
            ? 'expired'
            : $proposal->status;

        return [
            'id' => $proposal->public_id,
            'tool' => $proposal->tool,
            'title' => $definition?->title ?? $proposal->tool,
            'risk' => $proposal->risk,
            'requires_confirmation' => $proposal->requires_confirmation,
            'status' => $status,
            'expires_at' => $proposal->expires_at->toIso8601String(),
            'summary' => $this->proposalSummary( $proposal ),
        ];
    }

    private function proposalSummary( ActionProposal $proposal ): ?string
    {
        return match ( $proposal->tool ) {
            'import_products' => sprintf( __m( '%s products will be imported atomically.', 'NsOxen' ), count( $proposal->payload['products'] ?? [] ) ),
            'update_products' => sprintf( __m( '%s products will be updated atomically.', 'NsOxen' ), count( $proposal->payload['product_ids'] ?? [] ) ),
            'update_product_unit_quantities' => sprintf( __m( '%s product units will be updated atomically.', 'NsOxen' ), count( $proposal->payload['unit_quantity_ids'] ?? [] ) ),
            'create_product' => __m( 'One product will be created.', 'NsOxen' ),
            'create_provider' => __m( 'One provider will be created.', 'NsOxen' ),
            'create_customer' => __m( 'One customer will be created.', 'NsOxen' ),
            'generate_product_image' => __m( 'One image will be generated and assigned as primary.', 'NsOxen' ),
            default => null,
        };
    }

    private function storeId(): ?int
    {
        $store = isset( ns()->store ) && method_exists( ns()->store, 'getCurrentStore' ) ? ns()->store->getCurrentStore() : null;

        return $store?->id;
    }
}
