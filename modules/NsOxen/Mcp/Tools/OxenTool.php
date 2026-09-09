<?php

namespace Modules\NsOxen\Mcp\Tools;

use App\Models\User;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Modules\NsOxen\Data\OxenToolDefinition;
use Modules\NsOxen\Services\OxenException;
use Modules\NsOxen\Services\ToolRegistry;
use Throwable;

class OxenTool extends Tool
{
    public function __construct( private readonly OxenToolDefinition $definition ) {}

    public function name(): string
    {
        return $this->definition->name;
    }

    public function title(): string
    {
        return $this->definition->title;
    }

    public function description(): string
    {
        return strip_tags( $this->definition->description );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name(),
            'title' => $this->title(),
            'description' => $this->description(),
            'inputSchema' => $this->definition->inputSchema,
            'outputSchema' => $this->definition->outputSchema,
            'annotations' => [
                'readOnlyHint' => ! $this->definition->isWrite(),
                'destructiveHint' => $this->definition->risk === 'destructive',
                'idempotentHint' => $this->definition->isWrite(),
                'openWorldHint' => false,
            ],
        ];
    }

    public function shouldRegister( Request $request ): bool
    {
        $user = $request->user();

        return $user instanceof User && app( ToolRegistry::class )->available( $user, $this->definition->name );
    }

    public function handle( Request $request, ToolRegistry $registry ): Response
    {
        try {
            $user = $request->user();
            if ( ! $user instanceof User ) {
                throw new OxenException( 'UNAUTHENTICATED', __m( 'Authentication required.', 'NsOxen' ), 401 );
            }

            return Response::structured( $registry->execute( $user, $this->definition->name, $request->all() ) );
        } catch ( OxenException $exception ) {
            return Response::error( $exception->errorCode . ': ' . $exception->getMessage() );
        } catch ( Throwable $exception ) {
            report( $exception );

            return Response::error( 'TOOL_FAILED: ' . __m( 'Oxen could not complete the operation.', 'NsOxen' ) );
        }
    }
}
