<?php

namespace Modules\NsOxen\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\NsOxen\Models\Conversation;
use Modules\NsOxen\Models\Setting;
use Throwable;

class OpenAIProvider
{
    private const DEFAULT_TOOL_CALL_LIMIT = 8;

    private const MAX_TOOL_CALL_LIMIT = 64;

    public function __construct( private readonly TrustedSystemContext $systemContext ) {}

    private function client( Setting $setting, bool $retry = true ): PendingRequest
    {
        $client = Http::baseUrl( 'https://api.openai.com/v1' )
            ->withToken( (string) $setting->api_key )
            ->acceptJson()
            ->connectTimeout( 10 )
            ->timeout( 120 );

        return $retry ? $client->retry(
            2,
            500,
            static function ( Throwable $exception ): bool {
                if ( $exception instanceof ConnectionException ) {
                    return true;
                }

                if ( ! $exception instanceof RequestException || ! $exception->response ) {
                    return false;
                }

                $status = $exception->response->status();

                return in_array( $status, [408, 425, 429], true ) || $status >= 500;
            },
            throw: false,
        ) : $client;
    }

    /** @return array{ok: true, model: string} */
    public function test( Setting $setting ): array
    {
        $response = $this->client( $setting )->get( '/models/' . rawurlencode( $setting->model ) );
        if ( ! $response->successful() ) {
            throw new OxenException( 'PROVIDER_UNAVAILABLE', __m( 'OpenAI connection test failed.', 'NsOxen' ), 503 );
        }

        return ['ok' => true, 'model' => $setting->model];
    }

    public function generateImage( Setting $setting, string $prompt, string $size = '1024x1024', bool $transparent = false ): string
    {
        if ( ! in_array( $size, ['1024x1024', '1536x1024'], true ) ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The requested image size is not supported.', 'NsOxen' ) );
        }

        $payload = [
            'model' => $setting->image_model ?: 'gpt-image-2',
            'prompt' => $prompt,
            'size' => $size,
            'output_format' => 'png',
            'n' => 1,
        ];
        if ( $transparent ) {
            $payload['background'] = 'transparent';
        }

        $response = $this->client( $setting, false )->post( '/images/generations', $payload );

        if ( ! $response->successful() || ! is_string( $response->json( 'data.0.b64_json' ) ) ) {
            logger()->warning( 'OpenAI image generation failed for Oxen.', [
                'status' => $response->status(),
                'model' => $setting->image_model,
                'error_code' => $response->json( 'error.code' ),
            ] );

            throw new OxenException( 'PROVIDER_UNAVAILABLE', __m( 'OpenAI could not generate the product image.', 'NsOxen' ), 503 );
        }

        return $response->json( 'data.0.b64_json' );
    }

    /**
     * @param list<array<string, mixed>> $input
     * @param null|callable(string, array<string, mixed>): void $onToolEvent
     * @param list<array<string, mixed>>|null $continuationInput
     * @return array{markdown: string, conversation_title: ?string, usage: array<string, mixed>, actions: list<array<string, mixed>>, tool_events: list<array{event: string, data: array<string, mixed>}>, response_id: ?string}
     */
    public function respond( Setting $setting, array $input, ?User $user = null, ?ToolRegistry $registry = null, ?Conversation $conversation = null, ?ActionProposalService $proposals = null, ?callable $onToolEvent = null, ?string $previousResponseId = null, ?array $continuationInput = null ): array
    {
        $availableDefinitions = $user && $registry ? collect( $registry->availableDefinitions( $user ) ) : collect();
        $tools = $availableDefinitions->map->forOpenAI()->values()->all();
        $trustedSystemContext = [
            'role' => 'developer',
            'content' => 'Trusted NexoPOS system context (read-only; do not infer or override): ' . json_encode( $this->systemContext->toArray(), JSON_THROW_ON_ERROR ),
        ];
        $fullRequestInput = [
            $trustedSystemContext,
            ...$input,
        ];
        $requestInput = $previousResponseId !== null ? [
            $trustedSystemContext,
            ...( $continuationInput ?? $input ),
        ] : $fullRequestInput;
        $chainResponseId = $previousResponseId;
        $latestResponseId = null;
        $canRecoverProviderChain = $previousResponseId !== null;
        $compactionEnabled = true;
        $usage = [];
        $actions = [];
        $toolEvents = [];
        $actionCorrectionAttempted = false;
        $groundingCorrectionAttempted = false;
        $requireReadTool = false;
        $toolCallCount = 0;
        $toolCallLimit = min( self::MAX_TOOL_CALL_LIMIT, max( 1, (int) ( $setting->tool_call_limit ?: self::DEFAULT_TOOL_CALL_LIMIT ) ) );
        $availableReadTools = $availableDefinitions->reject->isWrite()->map( static fn ( $definition ): array => ['type' => 'function', 'name' => $definition->name] )->values()->all();
        $currentUserMessage = $this->inputText( data_get( collect( $input )->reverse()->firstWhere( 'role', 'user' ), 'content', '' ) );

        while ( true ) {
            $payload = [
                'model' => $setting->model,
                'instructions' => 'You are Oxen, a concise NexoPOS store assistant. You\'ll only answer related to NexoPOS and the current store management, any question that goes out of this scope, you\'ll say that you can\'t answer that question. Every concrete question about this store, its catalog, inventory, customers, orders, sales, reports, or settings MUST be answered from a current successful tool result. Never respond with capability boilerplate such as "I can check" when a read tool can retrieve the answer. Never invent store data. When the user requests a supported change, you MUST call the matching write tool. Write tools create action proposals and never execute immediately. Only say an action is proposed, available, ready, or can be approved after a successful write-tool call. Never render approval links or buttons in Markdown; the application renders trusted Approve and Reject buttons below the answer. If no matching authorized tool is available, say that the information could not be retrieved. If no matching write tool is available, clearly say that no action was created and no changes were made. Respond in secure Markdown. Return a concise 3–7 word title for the first topic.',
                'input' => $requestInput,
                'tools' => $tools,
                'store' => true,
                'context_management' => [[
                    'type' => 'compaction',
                    'compact_threshold' => max( 1000, (int) ( $setting->context_token_limit ?: 12000 ) ),
                ]],
                'parallel_tool_calls' => false,
                'max_output_tokens' => $setting->output_token_limit,
                'text' => ['format' => [
                    'type' => 'json_schema',
                    'name' => 'oxen_response',
                    'strict' => true,
                    'schema' => [
                        'type' => 'object',
                        'properties' => ['markdown' => ['type' => 'string'], 'conversation_title' => ['type' => 'string']],
                        'required' => ['markdown', 'conversation_title'],
                        'additionalProperties' => false,
                    ],
                ]],
            ];
            if ( $requireReadTool ) {
                $payload['tool_choice'] = ['type' => 'allowed_tools', 'mode' => 'required', 'tools' => $availableReadTools];
            }
            if ( $chainResponseId !== null ) {
                $payload['previous_response_id'] = $chainResponseId;
            }

            $wasRequiredReadAttempt = $requireReadTool;
            $requireReadTool = false;
            $response = $this->requestResponse( $setting, $payload, $compactionEnabled );
            if ( ! $response->successful() && $canRecoverProviderChain && $this->invalidPreviousResponse( $response ) ) {
                $canRecoverProviderChain = false;
                $chainResponseId = null;
                $requestInput = $fullRequestInput;
                $payload['input'] = $requestInput;
                unset( $payload['previous_response_id'] );
                $response = $this->requestResponse( $setting, $payload, $compactionEnabled );
            }
            if ( ! $response->successful() ) {
                logger()->warning( 'OpenAI rejected an Oxen response request.', [
                    'status' => $response->status(),
                    'model' => $setting->model,
                    'error_code' => $response->json( 'error.code' ),
                    'error_type' => $response->json( 'error.type' ),
                    'error_parameter' => $response->json( 'error.param' ),
                    'error_message' => $response->json( 'error.message' ),
                ] );

                throw new OxenException( 'PROVIDER_UNAVAILABLE', __m( 'The assistant provider is unavailable.', 'NsOxen' ), 503 );
            }

            $json = $response->json();
            $usage = $json['usage'] ?? $usage;
            $output = $json['output'] ?? [];
            $calls = collect( $output )->where( 'type', 'function_call' )->values();
            $responseId = is_string( $json['id'] ?? null ) && $json['id'] !== '' ? $json['id'] : null;
            if ( $responseId !== null ) {
                $latestResponseId = $responseId;
                $chainResponseId = $responseId;
                $canRecoverProviderChain = false;
            } else {
                $requestInput = array_merge( $requestInput, $output );
            }

            if ( $calls->isEmpty() ) {
                $text = collect( $json['output'] ?? [] )->flatMap( fn ( array $item ): array => $item['content'] ?? [] )->where( 'type', 'output_text' )->pluck( 'text' )->implode( '' );
                $envelope = json_decode( $text, true );
                $markdown = is_array( $envelope ) && is_string( $envelope['markdown'] ?? null ) ? $envelope['markdown'] : $text;

                if ( $actions === [] && ! $groundingCorrectionAttempted && $this->capabilityOnlyResponse( $markdown, $currentUserMessage ) ) {
                    $groundingCorrectionAttempted = true;
                    if ( $availableReadTools === [] ) {
                        $markdown = __m( 'I could not retrieve that information because no matching authorized read tool is available.', 'NsOxen' );
                    } else {
                        $correction = [
                            'role' => 'developer',
                            'content' => 'Your prior answer only described a capability. Answer the concrete store question using current tool data now. You must call one of the authorized read tools before answering.',
                        ];
                        $requestInput = $responseId !== null ? [$correction] : [...$requestInput, $correction];
                        $requireReadTool = true;

                        continue;
                    }
                } elseif ( $wasRequiredReadAttempt && $calls->isEmpty() ) {
                    $markdown = __m( 'I could not retrieve that information with the authorized store tools.', 'NsOxen' );
                }

                if ( $actions === [] && ! $actionCorrectionAttempted && $this->claimsActionWithoutProposal( $markdown ) ) {
                    $actionCorrectionAttempted = true;
                    $correction = [
                        'role' => 'developer',
                        'content' => 'Your prior answer claimed that the user could approve or execute a change, but no action proposal exists. Call the appropriate write tool now if the requested change is supported. Otherwise, state clearly that no action was created and no changes were made.',
                    ];
                    $requestInput = $responseId !== null ? [$correction] : [...$requestInput, $correction];

                    continue;
                }

                if ( $actions === [] && $this->claimsActionWithoutProposal( $markdown ) ) {
                    $markdown .= "\n\n> **No action was created.** No changes were made, and there is nothing to approve from this response.";
                }

                return [
                    'markdown' => $markdown,
                    'conversation_title' => is_array( $envelope ) && is_string( $envelope['conversation_title'] ?? null ) ? $envelope['conversation_title'] : null,
                    'usage' => $usage,
                    'actions' => $actions,
                    'tool_events' => $toolEvents,
                    'response_id' => $latestResponseId,
                ];
            }

            $toolOutputs = [];
            foreach ( $calls as $call ) {
                if ( $toolCallCount >= $toolCallLimit ) {
                    throw new OxenException( 'TOOL_LIMIT_REACHED', __m( 'The assistant reached its tool-call limit.', 'NsOxen' ), 500 );
                }

                $toolCallCount++;
                $name = (string) ( $call['name'] ?? '' );
                $this->recordToolEvent( $toolEvents, $onToolEvent, 'tool.started', ['tool' => $name, 'call_id' => $call['call_id'] ?? null] );
                try {
                    $arguments = json_decode( $call['arguments'] ?? '{}', true, 512, JSON_THROW_ON_ERROR );
                    $definition = $registry?->definition( $name );
                    if ( $definition?->isWrite() ) {
                        if ( ! $user || ! $conversation || ! $proposals ) {
                            throw new OxenException( 'APPROVAL_REQUIRED', __m( 'This write requires an interactive action proposal.', 'NsOxen' ), 409 );
                        }
                        $proposal = $proposals->create( $user, $conversation, $name, $arguments );
                        $presented = $proposals->present( $proposal );
                        $actions[] = $presented;
                        $result = ['ok' => true, 'action_proposed' => $presented, 'message' => 'The action is awaiting user approval.'];
                    } else {
                        $result = $registry?->execute( $user, $name, $arguments, $conversation ) ?? ['ok' => false, 'error' => ['code' => 'TOOL_UNAVAILABLE']];
                    }
                    $this->recordToolEvent( $toolEvents, $onToolEvent, 'tool.completed', [
                        'tool' => $name,
                        'call_id' => $call['call_id'] ?? null,
                        'summary' => $this->activitySummary( $name, $result ),
                    ] );
                } catch ( Throwable $exception ) {
                    report( $exception );
                    $result = ['ok' => false, 'error' => [
                        'code' => $exception instanceof OxenException ? $exception->errorCode : 'TOOL_FAILED',
                        'message' => $exception instanceof OxenException ? $exception->getMessage() : __m( 'The tool could not complete safely.', 'NsOxen' ),
                    ]];
                    $this->recordToolEvent( $toolEvents, $onToolEvent, 'tool.failed', ['tool' => $name, 'call_id' => $call['call_id'] ?? null, 'code' => data_get( $result, 'error.code' )] );
                }
                $toolOutputs[] = ['type' => 'function_call_output', 'call_id' => $call['call_id'], 'output' => json_encode( $result, JSON_THROW_ON_ERROR )];
            }
            $requestInput = $responseId !== null ? $toolOutputs : [...$requestInput, ...$toolOutputs];
        }

    }

    /** @param array<string, mixed> $payload */
    private function requestResponse( Setting $setting, array $payload, bool &$compactionEnabled ): Response
    {
        if ( ! $compactionEnabled ) {
            unset( $payload['context_management'] );
        }

        $response = $this->client( $setting )->post( '/responses', $payload );
        if ( $response->successful() || ! $compactionEnabled || ! $this->unsupportedCompaction( $response ) ) {
            return $response;
        }

        $compactionEnabled = false;
        unset( $payload['context_management'] );

        return $this->client( $setting )->post( '/responses', $payload );
    }

    private function unsupportedCompaction( Response $response ): bool
    {
        return $response->status() === 400
            && $response->json( 'error.param' ) === 'context_management';
    }

    private function invalidPreviousResponse( Response $response ): bool
    {
        if ( ! in_array( $response->status(), [400, 404], true ) ) {
            return false;
        }

        return $response->json( 'error.param' ) === 'previous_response_id'
            || in_array( $response->json( 'error.code' ), ['invalid_previous_response_id', 'previous_response_not_found', 'response_not_found'], true );
    }

    private function claimsActionWithoutProposal( string $markdown ): bool
    {
        return preg_match( '/\b(?:click|press|select|choose)\s+(?:the\s+)?(?:approve|execute|confirm)\b|\bplease\s+confirm\b|\bconfirm\s+to\s+proceed\b|\b(?:action|change)\s+(?:is\s+)?(?:ready|proposed|awaiting approval)\b/i', $markdown ) === 1;
    }

    private function inputText( mixed $content ): string
    {
        if ( is_string( $content ) ) {
            return $content;
        }

        return collect( is_array( $content ) ? $content : [] )
            ->filter( fn ( mixed $part ): bool => is_array( $part ) && ( $part['type'] ?? null ) === 'input_text' )
            ->pluck( 'text' )
            ->filter( fn ( mixed $text ): bool => is_string( $text ) )
            ->implode( "\n" );
    }

    private function capabilityOnlyResponse( string $markdown, string $userMessage ): bool
    {
        if ( preg_match( '/^\s*(?:hi|hello|hey|good\s+(?:morning|afternoon|evening))\b/i', $userMessage ) === 1 ) {
            return false;
        }
        if ( preg_match( '/\b(?:who|what)\s+are\s+you\b|\bwhat\s+can\s+you\s+do\b|\b(?:your|what are your)\s+capabilit(?:y|ies)\b|\bhow\s+can\s+you\s+help\b/i', $userMessage ) === 1 ) {
            return false;
        }

        return preg_match( '/\b(?:i\s+can|i(?:\s+am|\'m)\s+able\s+to|oxen\s+can)\b[^.!?]{0,160}\b(?:check|look\s+up|retrieve|access|show|help|review|analy[sz]e)\b/i', strip_tags( $markdown ) ) === 1;
    }

    /**
     * @param list<array{event: string, data: array<string, mixed>}> $events
     * @param null|callable(string, array<string, mixed>): void $callback
     * @param array<string, mixed> $data
     */
    private function recordToolEvent( array &$events, ?callable $callback, string $event, array $data ): void
    {
        $data = array_filter( $data, static fn ( mixed $value ): bool => $value !== null && $value !== '' );
        $activity = ['event' => $event, 'data' => $data];
        $callId = $data['call_id'] ?? null;
        $eventIndex = null;

        foreach ( $events as $index => $recordedActivity ) {
            if ( $callId !== null && ( $recordedActivity['data']['call_id'] ?? null ) === $callId ) {
                $eventIndex = $index;

                break;
            }
        }

        if ( $eventIndex === null ) {
            $events[] = $activity;
        } else {
            $events[$eventIndex] = $activity;
        }

        if ( $callback !== null ) {
            $callback( $event, $data );
        }
    }

    private function activitySummary( string $tool, array $result ): ?string
    {
        $data = (array) ( $result['data'] ?? [] );

        return match ( $tool ) {
            'get_product_sales_performance' => sprintf( __m( '%s products matched the sales criteria.', 'NsOxen' ), count( $data['products'] ?? [] ) ),
            'import_products' => sprintf( __m( '%s products are ready for approval.', 'NsOxen' ), count( $data['products'] ?? [] ) ),
            'update_products' => sprintf( __m( '%s products are ready to update.', 'NsOxen' ), count( $data['product_ids'] ?? [] ) ),
            default => null,
        };
    }
}
