<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Tools\Tool;
use App\Models\Media;
use App\Services\MediaService;

class DeleteMediaTool extends Tool
{
    public function name(): string
    {
        return 'delete_media';
    }

    public function description(): string
    {
        return 'Deletes a media item and its corresponding files from storage.';
    }

    public function parameters(): array
    {
        return [
            'id' => [
                'type' => 'integer',
                'description' => 'The ID of the media item to delete.',
                'required' => true,
            ],
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
