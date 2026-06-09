<?php

namespace App\Mcp\Tools;

use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use App\Models\Media;
use App\Services\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\JsonSchema\JsonSchema;

class UploadMediaTool extends Tool
{
    public string $name = 'upload_media';

    public string $description = 'Uploads a local file to the media library. Note: this tool requires a valid local file path within the workspace.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'file_path' => $schema->string()
                ->description('The absolute local path to the file to upload.')
                ->required(),
            'custom_name' => $schema->string()
                ->description('An optional custom name for the uploaded file (excluding extension).')
                ->nullable(),
        ];
    }

    public function handle(\Laravel\Mcp\Request $request): \Laravel\Mcp\Response
    {
        $filePath = $request->get('file_path');
        $customName = $request->get('custom_name') ?? null;

        if (!File::exists($filePath)) {
            $this->error("File not found at path: {$filePath}");
            return Response::text('');
        }

        $originalName = File::basename($filePath);
        $mimeType = File::mimeType($filePath);
        $size = File::size($filePath);

        // UploadedFile expects ($path, $originalName, $mimeType, $error, $test)
        // Set $test to true so it doesn\'t try to move uploaded file via PHP copy_uploaded_file which fails for non-HTTP uploads.
        $uploadedFile = new UploadedFile(
            $filePath,
            $originalName,
            $mimeType,
            null,
            true
        );

        $service = app()->make(MediaService::class);
        $media = $service->upload($uploadedFile, $customName);

        if (!$media) {
            $this->error('Failed to upload media. Ensure the file type is allowed (e.g., typical image types).');
            return Response::text('');
        }

        return json_encode([
            'id' => $media->id,
            'name' => $media->name,
            'path' => $media->path,
            'url' => $media->url,
            'message' => 'Media uploaded successfully.'
        ], JSON_PRETTY_PRINT);
    }
}
