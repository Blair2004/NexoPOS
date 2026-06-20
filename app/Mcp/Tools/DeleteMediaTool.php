<?php

namespace App\Mcp\Tools;

use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteMediaTool extends Tool
{
    public string $name = 'delete_media';

    public string $description = 'Deletes a media item and its corresponding files from storage.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'id' => $schema->integer()
                ->description( 'The ID of the media item to delete.' )
                ->required(),
        ];
    }

    public function handle( \Laravel\Mcp\Request $request ): \Laravel\Mcp\Response
    {
        $id = $request->get( 'id' );
        $media = Media::find( $id );

        if ( ! $media ) {
            $this->error( 'Media not found.' );

            return Response::text( '' );
        }

        $service = app()->make( MediaService::class );
        $result = $service->deleteMedia( $id );

        if ( isset( $result['status'] ) && $result['status'] === 'success' ) {
            return Response::text( "Media deleted successfully: {$result['message']}" );
        }

        $this->error( 'Failed to delete media.' );

        return Response::text( '' );
    }
}
