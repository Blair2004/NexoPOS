<?php

namespace Tests\Traits;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait TestsMcpTools
{
    /**
     * Run an MCP tool and return the parsed JSON response or the plain text.
     *
     * @return array|string
     */
    protected function runMcpTool( string $toolClass, array $args = [] )
    {
        /** @var \Laravel\Mcp\Server\Tool $tool */
        $tool = app()->make( $toolClass );

        $request = new Request( $args );

        /** @var Response $response */
        $response = app()->call( [$tool, 'handle'], ['request' => $request] );

        $content = (string) $response->content();

        if ( $response->isError() ) {
            return [
                'error' => true,
                'message' => $content,
            ];
        }

        $decoded = json_decode( $content, true );

        return ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : $content;
    }
}
