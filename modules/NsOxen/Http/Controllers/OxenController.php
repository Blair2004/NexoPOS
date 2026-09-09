<?php

namespace Modules\NsOxen\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\UserOptions;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\NsOxen\Http\Requests\ConversationRequest;
use Modules\NsOxen\Http\Requests\LauncherPreferenceRequest;
use Modules\NsOxen\Http\Requests\MessageRequest;
use Modules\NsOxen\Models\ActionProposal;
use Modules\NsOxen\Models\Conversation;
use Modules\NsOxen\Models\Message;
use Modules\NsOxen\Models\Setting;
use Modules\NsOxen\Models\Usage;
use Modules\NsOxen\Services\ActionProposalService;
use Modules\NsOxen\Services\ConversationAttachmentService;
use Modules\NsOxen\Services\ConversationContextBuilder;
use Modules\NsOxen\Services\OpenAIProvider;
use Modules\NsOxen\Services\OxenException;
use Modules\NsOxen\Services\ToolRegistry;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OxenController extends Controller
{
    private const STREAM_PRELUDE_BYTES = 4096;

    private const DEFAULT_LAUNCHER_PREFERENCE = [
        'icon' => ['edge' => 'right', 'offset_ratio' => 0.82],
        'popup' => ['x_ratio' => 1.0, 'y_ratio' => 1.0],
    ];

    public function csrfToken(): JsonResponse
    {
        return response()->json( ['token' => csrf_token()] );
    }

    public function bootstrap( Request $request ): JsonResponse
    {
        $setting = Setting::query()->first();

        $storedPreference = ( new UserOptions( $request->user()->id ) )->get( 'ns_oxen_launcher', [] );

        return response()->json( [
            'configured' => (bool) $setting?->api_key,
            'enabled' => (bool) $setting?->assistant_enabled,
            'can_manage' => ns()->allowedTo( 'ns.oxen.manage' ),
            'preference' => $this->normalizeLauncherPreference( $storedPreference ),
        ] );
    }

    public function index( Request $request ): JsonResponse
    {
        return response()->json( Conversation::query()->where( 'user_id', $request->user()->id )->latest( 'last_activity_at' )->limit( 50 )->get( ['public_id', 'title', 'status', 'last_activity_at'] ) );
    }

    public function tools( Request $request, ToolRegistry $registry ): JsonResponse
    {
        return response()->json( collect( $registry->availableDefinitions( $request->user() ) )
            ->map( fn ( $definition ): array => $definition->forCatalog() )
            ->values() );
    }

    public function executeAction( Request $request, string $publicId, ActionProposalService $proposals ): JsonResponse
    {
        try {
            return response()->json( $proposals->execute( $request->user(), $publicId ) );
        } catch ( OxenException $exception ) {
            return response()->json( ['error' => ['code' => $exception->errorCode, 'message' => $exception->getMessage()]], $exception->httpStatus );
        } catch ( ModelNotFoundException ) {
            return response()->json( ['error' => ['code' => 'NOT_FOUND', 'message' => __m( 'Action not found.', 'NsOxen' )]], 404 );
        } catch ( \Throwable $exception ) {
            report( $exception );

            return response()->json( ['error' => ['code' => 'TOOL_FAILED', 'message' => __m( 'The approved action could not be completed safely.', 'NsOxen' )]], 500 );
        }
    }

    public function rejectAction( Request $request, string $publicId, ActionProposalService $proposals ): JsonResponse
    {
        try {
            return response()->json( $proposals->reject( $request->user(), $publicId ) );
        } catch ( OxenException $exception ) {
            return response()->json( ['error' => ['code' => $exception->errorCode, 'message' => $exception->getMessage()]], $exception->httpStatus );
        } catch ( ModelNotFoundException ) {
            return response()->json( ['error' => ['code' => 'NOT_FOUND', 'message' => __m( 'Action not found.', 'NsOxen' )]], 404 );
        }
    }

    public function retryAction( Request $request, string $publicId, ActionProposalService $proposals ): JsonResponse
    {
        try {
            return response()->json( $proposals->retry( $request->user(), $publicId ), 201 );
        } catch ( OxenException $exception ) {
            return response()->json( ['error' => ['code' => $exception->errorCode, 'message' => $exception->getMessage()]], $exception->httpStatus );
        } catch ( ModelNotFoundException ) {
            return response()->json( ['error' => ['code' => 'NOT_FOUND', 'message' => __m( 'Action not found.', 'NsOxen' )]], 404 );
        }
    }

    public function store( ConversationRequest $request ): JsonResponse
    {
        $setting = Setting::query()->firstOrCreate( ['provider' => 'openai'] );
        $conversation = Conversation::query()->create( ['public_id' => (string) Str::uuid(), 'user_id' => $request->user()->id, 'title' => $request->validated( 'title', __m( 'New conversation', 'NsOxen' ) ), 'provider' => $setting->provider, 'model' => $setting->model, 'status' => 'active', 'last_activity_at' => now()] );

        return response()->json( $conversation, 201 );
    }

    public function show( Request $request, string $publicId, ActionProposalService $proposals, ConversationAttachmentService $attachments ): JsonResponse
    {
        $conversation = $this->owned( $request, $publicId );
        $messages = $conversation->messages()->get( ['id', 'role', 'content', 'metadata', 'status', 'created_at'] );
        $actionIds = $messages->flatMap( fn ( Message $message ): array => data_get( $message->metadata, 'actions', [] ) )
            ->pluck( 'id' )
            ->filter()
            ->unique()
            ->values();
        $currentActions = ActionProposal::query()
            ->where( 'user_id', $request->user()->id )
            ->where( 'conversation_id', $conversation->id )
            ->whereIn( 'public_id', $actionIds )
            ->get()
            ->mapWithKeys( fn ( ActionProposal $proposal ): array => [$proposal->public_id => $proposals->present( $proposal )] );

        $messages->each( function ( Message $message ) use ( $currentActions, $attachments ): void {
            $metadata = $message->metadata ?? [];
            $metadata['actions'] = collect( $metadata['actions'] ?? [] )
                ->map( fn ( array $action ): array => $currentActions->get( $action['id'] ?? '', $action ) )
                ->values()
                ->all();
            $metadata['attachments'] = $attachments->present( data_get( $metadata, 'attachments', [] ) );
            $message->metadata = $metadata;

            if ( data_get( $metadata, 'action_event' ) !== null ) {
                $message->setAttribute( 'type', 'info' );
            }
        } );

        return response()->json( ['conversation' => $conversation->only( ['public_id', 'title', 'status', 'last_activity_at'] ), 'messages' => $messages] );
    }

    public function update( ConversationRequest $request, string $publicId ): JsonResponse
    {
        $conversation = $this->owned( $request, $publicId );
        $conversation->update( $request->validated() );

        return response()->json( $conversation );
    }

    public function destroy( Request $request, string $publicId, ConversationAttachmentService $attachments ): JsonResponse
    {
        $conversation = $this->owned( $request, $publicId );
        DB::transaction( fn() => [$conversation->messages()->delete(), $conversation->delete()] );
        $attachments->deleteConversation( $publicId );

        return response()->json( ['ok' => true] );
    }

    public function preference( LauncherPreferenceRequest $request ): JsonResponse
    {
        $preference = $this->normalizeLauncherPreference( $request->validated() );
        ( new UserOptions( $request->user()->id ) )->set( 'ns_oxen_launcher', $preference );

        return response()->json( $preference );
    }

    public function message( MessageRequest $request, string $publicId, OpenAIProvider $provider, ToolRegistry $registry, ActionProposalService $proposals, ConversationContextBuilder $contextBuilder, ConversationAttachmentService $attachments ): StreamedResponse
    {
        $conversation = $this->owned( $request, $publicId );
        $setting = Setting::query()->first();

        return response()->stream( function () use ( $request, $conversation, $setting, $provider, $registry, $proposals, $contextBuilder, $attachments ): void {
            $toolEvents = [];
            $turnLock = Cache::lock( 'ns-oxen:conversation-turn:' . $conversation->id, 300 );
            $turnLockAcquired = false;
            echo ': ' . str_repeat( ' ', self::STREAM_PRELUDE_BYTES ) . "\n\n";
            if ( ob_get_level() > 0 ) {
                ob_flush();
            }
            flush();

            $send = static function ( string $event, array $data ): void {
                echo "event: {$event}\ndata: " . json_encode( $data, JSON_THROW_ON_ERROR ) . "\n\n";
                if ( ob_get_level() > 0 ) {
                    ob_flush();
                } flush();
            };
            $sendToolEvent = function ( string $event, array $data ) use ( $send, &$toolEvents ): void {
                $this->recordToolActivity( $toolEvents, $event, $data );
                $send( $event, $data );
            };
            try {
                $turnLockAcquired = $turnLock->get();
                if ( ! $turnLockAcquired ) {
                    throw new OxenException( 'CONVERSATION_BUSY', __m( 'This conversation is already processing another turn.', 'NsOxen' ), 409 );
                }

                $conversation->refresh();
                $send( 'turn.started', ['conversation_id' => $conversation->public_id] );
                $messageText = (string) $request->validated( 'message', '' );
                $metadata = ['route_name' => $request->validated( 'route_name' ), 'entity_id' => $request->validated( 'entity_id' )];
                $currentMessage = Message::query()->create( ['conversation_id' => $conversation->id, 'role' => 'user', 'content' => $messageText, 'metadata' => $metadata] );
                $metadata['attachments'] = $attachments->store( $conversation, $currentMessage, $request->file( 'attachments', [] ) );
                $currentMessage->update( ['metadata' => $metadata] );
                $currentMessage->refresh();
                $send( 'message.accepted', ['attachments' => $attachments->present( $metadata['attachments'] )] );

                if ( ! $setting?->assistant_enabled || ! $setting->api_key ) {
                    throw new OxenException( 'PROVIDER_UNAVAILABLE', __m( 'Oxen is not configured.', 'NsOxen' ), 503 );
                }
                $usage = Usage::query()->firstOrCreate( ['user_id' => $request->user()->id, 'usage_date' => now()->toDateString()] );
                if ( $usage->messages >= $setting->daily_message_limit ) {
                    throw new OxenException( 'RATE_LIMITED', __m( 'Daily assistant limit reached.', 'NsOxen' ), 429 );
                }
                $context = $contextBuilder->build( $conversation, $currentMessage, (int) $setting->context_token_limit );
                $previousResponseId = $conversation->model === $setting->model
                    && is_string( $conversation->provider_response_id )
                    && $conversation->provider_response_id !== ''
                    && is_int( $conversation->provider_response_message_id )
                        ? $conversation->provider_response_id
                        : null;
                $continuationContext = $previousResponseId !== null
                    ? $contextBuilder->buildContinuation( $conversation, $currentMessage, (int) $setting->context_token_limit, $conversation->provider_response_message_id )
                    : null;
                $result = $provider->respond(
                    $setting,
                    $context,
                    $request->user(),
                    $registry,
                    $conversation,
                    $proposals,
                    $sendToolEvent,
                    $previousResponseId,
                    $continuationContext,
                );
                $assistantMessage = Message::query()->create( ['conversation_id' => $conversation->id, 'role' => 'assistant', 'content' => $result['markdown'], 'metadata' => ['usage' => $result['usage'], 'actions' => $result['actions'], 'activity' => $result['tool_events']]] );
                if ( $result['actions'] !== [] ) {
                    $proposals->attachToMessage( collect( $result['actions'] )->pluck( 'id' )->all(), $assistantMessage );
                }
                $updates = ['last_activity_at' => now()];
                if ( is_string( $result['response_id'] ?? null ) && $result['response_id'] !== '' ) {
                    $updates['provider_response_id'] = $result['response_id'];
                    $updates['provider_response_message_id'] = $assistantMessage->id;
                    $updates['model'] = $setting->model;
                }
                if ( $conversation->title === __m( 'New conversation', 'NsOxen' ) ) {
                    $titleSource = $messageText !== '' ? $messageText : (string) data_get( $metadata, 'attachments.0.name', __m( 'Attachment', 'NsOxen' ) );
                    $updates['title'] = $this->conversationTitle( $result['conversation_title'], $titleSource );
                }
                $conversation->update( $updates );
                $usage->increment( 'messages' );
                foreach ( str_split( $result['markdown'], 120 ) as $delta ) {
                    $send( 'text.delta', ['delta' => $delta] );
                }
                if ( $result['actions'] !== [] ) {
                    $send( 'actions.available', ['actions' => $result['actions']] );
                    if ( collect( $result['actions'] )->contains( 'requires_confirmation', true ) ) {
                        $send( 'approval.required', ['actions' => collect( $result['actions'] )->where( 'requires_confirmation', true )->values()->all()] );
                    }
                }
                if ( isset( $updates['title'] ) ) {
                    $send( 'conversation.updated', ['conversation_id' => $conversation->public_id, 'title' => $updates['title']] );
                }
                $send( 'usage.updated', $result['usage'] );
                $send( 'turn.completed', ['message_id' => $assistantMessage->id] );
            } catch ( OxenException $e ) {
                $assistantMessage = $this->recordFailedTurn( $conversation, $e->errorCode, $e->getMessage(), $toolEvents );
                $send( 'text.delta', ['delta' => $e->getMessage()] );
                $send( 'turn.failed', ['code' => $e->errorCode, 'message' => $e->getMessage(), 'message_id' => $assistantMessage?->id] );
            } catch ( \Throwable $e ) {
                report( $e );
                logger()->error( 'Oxen assistant turn failed.', ['conversation_id' => $conversation->public_id, 'exception' => $e::class] );
                $message = __m( 'Oxen could not complete this turn.', 'NsOxen' );
                $assistantMessage = $this->recordFailedTurn( $conversation, 'INTERNAL_ERROR', $message, $toolEvents );
                $send( 'text.delta', ['delta' => $message] );
                $send( 'turn.failed', ['code' => 'INTERNAL_ERROR', 'message' => $message, 'message_id' => $assistantMessage?->id] );
            } finally {
                if ( $turnLockAcquired ) {
                    $turnLock->release();
                }
            }
        }, 200, [
            'Content-Type' => 'text/event-stream; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-transform',
            'X-Accel-Buffering' => 'no',
        ] );
    }

    /** @param list<array{event: string, data: array<string, mixed>}> $toolEvents */
    private function recordFailedTurn( Conversation $conversation, string $code, string $message, array $toolEvents = [] ): ?Message
    {
        try {
            $assistantMessage = $conversation->messages()->create( [
                'role' => 'assistant',
                'content' => $message,
                'metadata' => ['error' => ['code' => $code], 'activity' => $toolEvents],
                'status' => 'failed',
            ] );
            $conversation->update( ['last_activity_at' => now()] );

            return $assistantMessage;
        } catch ( \Throwable $exception ) {
            report( $exception );

            return null;
        }
    }

    /**
     * @param list<array{event: string, data: array<string, mixed>}> $toolEvents
     * @param array<string, mixed>                                   $data
     */
    private function recordToolActivity( array &$toolEvents, string $event, array $data ): void
    {
        $activity = ['event' => $event, 'data' => $data];
        $callId = $data['call_id'] ?? null;
        $eventIndex = null;

        foreach ( $toolEvents as $index => $recordedActivity ) {
            if ( $callId !== null && ( $recordedActivity['data']['call_id'] ?? null ) === $callId ) {
                $eventIndex = $index;

                break;
            }
        }

        if ( $eventIndex === null ) {
            $toolEvents[] = $activity;
        } else {
            $toolEvents[$eventIndex] = $activity;
        }
    }

    private function owned( Request $request, string $publicId ): Conversation
    {
        return Conversation::query()->where( 'user_id', $request->user()->id )->where( 'public_id', $publicId )->firstOrFail();
    }

    private function conversationTitle( mixed $candidate, string $message ): string
    {
        $title = trim( strip_tags( is_string( $candidate ) ? $candidate : '' ) );
        $words = preg_split( '/\s+/', $title, -1, PREG_SPLIT_NO_EMPTY ) ?: [];
        if ( count( $words ) < 3 || count( $words ) > 7 || mb_strlen( $title ) > 80 ) {
            $title = Str::of( $message )->squish()->words( 7, '' )->limit( 80, '' )->toString();
        }

        return $title !== '' ? $title : __m( 'New store discussion', 'NsOxen' );
    }

    /**
     * @return array{icon: array{edge: string, offset_ratio: float}, popup: array{x_ratio: float, y_ratio: float}}
     */
    private function normalizeLauncherPreference( mixed $storedPreference ): array
    {
        if ( ! is_array( $storedPreference ) ) {
            return self::DEFAULT_LAUNCHER_PREFERENCE;
        }

        $iconPreference = is_array( $storedPreference['icon'] ?? null )
            ? $storedPreference['icon']
            : $storedPreference;
        $popupPreference = is_array( $storedPreference['popup'] ?? null )
            ? $storedPreference['popup']
            : [];

        return [
            'icon' => [
                'edge' => in_array( $iconPreference['edge'] ?? null, ['left', 'right', 'top', 'bottom'], true )
                    ? $iconPreference['edge']
                    : self::DEFAULT_LAUNCHER_PREFERENCE['icon']['edge'],
                'offset_ratio' => $this->normalizedRatio(
                    $iconPreference['offset_ratio'] ?? null,
                    self::DEFAULT_LAUNCHER_PREFERENCE['icon']['offset_ratio'],
                ),
            ],
            'popup' => [
                'x_ratio' => $this->normalizedRatio(
                    $popupPreference['x_ratio'] ?? null,
                    self::DEFAULT_LAUNCHER_PREFERENCE['popup']['x_ratio'],
                ),
                'y_ratio' => $this->normalizedRatio(
                    $popupPreference['y_ratio'] ?? null,
                    self::DEFAULT_LAUNCHER_PREFERENCE['popup']['y_ratio'],
                ),
            ],
        ];
    }

    private function normalizedRatio( mixed $ratio, float $default ): float
    {
        if ( ! is_numeric( $ratio ) ) {
            return $default;
        }

        $normalizedRatio = (float) $ratio;

        return is_finite( $normalizedRatio ) && $normalizedRatio >= 0 && $normalizedRatio <= 1
            ? $normalizedRatio
            : $default;
    }
}
