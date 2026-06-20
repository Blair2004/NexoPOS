<?php

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateSettingsTool extends Tool
{
    public string $name = 'update_settings';

    public string $description = 'Updates NexoPOS configuration settings via ns()->option->set(key, value).';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'settings' => $schema->object()
                ->description( 'A key-value pair of settings to update (e.g. {"ns_store_name": "My Shop"})' )
                ->required(),
        ];
    }

    public function handle( \Laravel\Mcp\Request $request ): \Laravel\Mcp\Response
    {
        if ( empty( $request->get( 'settings' ) ) || ! is_array( $request->get( 'settings' ) ) ) {
            return Response::error( 'The settings parameter must be a non-empty object containing key-value pairs.' );
        }

        $applied = [];

        foreach ( $request->get( 'settings' ) as $key => $value ) {
            ns()->option->set( $key, $value );
            $applied[] = $key;
        }

        return Response::json( [
            'applied_keys' => $applied,
            'message' => 'Settings updated successfully.',
        ] );
    }
}
