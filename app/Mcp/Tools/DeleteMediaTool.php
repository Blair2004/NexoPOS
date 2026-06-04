<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Server\Tool;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class DeleteMediaTool extends Tool
{
    public string $name = 'delete_media';

    public string $description = 'Deletes a media item and its corresponding files from storage.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()
                ->description('The ID of the media item to delete.')
                ->required(),
        ];
    }

    public function handle(array $parameters): string
    {
        $id = $parameters['id'];
        $media = Media::find($id);

        if (!$media) {
            $this->error('Media not found.');
            return '';
        }

        $service = app()->make(MediaService::class);
        $result = $service->deleteMedia($id);

        if (isset($result['status']) && $result['status'] === 'success') {
            return "Media deleted successfully: {$result['message']}";
        }

        $this->error('Failed to delete media.');
        return '';
    }
}
