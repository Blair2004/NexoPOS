<?php

namespace App\Mcp\Tools;

use App\Services\MediaService;
use finfo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UploadMediaTool extends Tool
{
    public string $name = 'upload_media';

    public string $description = 'Uploads a local file to the media library. Note: this tool requires a valid local file path within the workspace.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'base64_file' => $schema->string()
                ->description( 'The base64-encoded content of the file to upload. This is an alternative to providing a file path and can be used for files that are not accessible via a local path.' )
                ->nullable(),
            'file_path' => $schema->string()
                ->description( 'The absolute local path to the file to upload.' )
                ->nullable(),
            'custom_name' => $schema->string()
                ->description( 'An optional custom name for the uploaded file (excluding extension).' )
                ->nullable(),
        ];
    }

    public function handle( Request $request ): Response
    {
        $filePath = $request->get( 'file_path' );
        $customName = $request->get( 'custom_name' ) ?? null;

        if ( File::exists( $filePath ) ) {
            $originalName = File::basename( $filePath );
            $mimeType = File::mimeType( $filePath );
            $size = File::size( $filePath );

            // UploadedFile expects ($path, $originalName, $mimeType, $error, $test)
            // Set $test to true so it doesn\'t try to move uploaded file via PHP copy_uploaded_file which fails for non-HTTP uploads.
            $uploadedFile = new UploadedFile(
                $filePath,
                $originalName,
                $mimeType,
                null,
                true
            );

            $service = app()->make( MediaService::class );
            $media = $service->upload( $uploadedFile, $customName );

            if ( ! $media ) {
                return Response::error( __( 'Failed to upload media. Please check the file path and try again.' ) );
            }

            return Response::json( [
                'id' => $media->id,
                'name' => $media->name,
                'path' => $media->path,
                'url' => $media->url,
                'message' => __( 'Media uploaded successfully.' ),
            ] );
        } elseif ( ! empty( $request->get( 'base64_file' ) ) ) {
            $base64File = $request->get( 'base64_file' );

            // Supports both raw base64 and data URI:
            // data:image/png;base64,iVBORw0KGgo...
            $mimeType = null;
            $extension = null;

            if ( preg_match( '/^data:(.*?);base64,(.*)$/', $base64File, $matches ) ) {
                $mimeType = $matches[1];
                $base64File = $matches[2];
            }

            $decodedFile = base64_decode( $base64File, true );

            if ( $decodedFile === false ) {
                return Response::error( __( 'Invalid base64 file provided.' ) );
            }

            if ( ! $mimeType ) {
                $finfo = new finfo( FILEINFO_MIME_TYPE );
                $mimeType = $finfo->buffer( $decodedFile ) ?: 'application/octet-stream';
            }

            $extension = match ( $mimeType ) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
                default => 'bin',
            };

            $originalName = $customName
                ? $customName . '.' . $extension
                : 'uploaded-file-' . uniqid() . '.' . $extension;

            $temporaryPath = storage_path( 'app/tmp/' . uniqid( 'media_', true ) . '.' . $extension );

            if ( ! File::exists( dirname( $temporaryPath ) ) ) {
                File::makeDirectory( dirname( $temporaryPath ), 0755, true );
            }

            File::put( $temporaryPath, $decodedFile );

            $uploadedFile = new UploadedFile(
                $temporaryPath,
                $originalName,
                $mimeType,
                null,
                true
            );

            $service = app()->make( MediaService::class );
            $media = $service->upload( $uploadedFile, $customName );

            File::delete( $temporaryPath );

            if ( ! $media ) {
                return Response::error( __( 'Failed to upload media from base64 file.' ) );
            }

            return Response::json( [
                'id' => $media->id,
                'name' => $media->name,
                'path' => $media->path,
                'url' => $media->url,
                'message' => __( 'Media uploaded successfully.' ),
            ] );
        } else {
            return Response::text(
                __( 'File not found at the specified path. Please provide a valid local file path or a base64-encoded file.' )
            );
        }
    }
}
