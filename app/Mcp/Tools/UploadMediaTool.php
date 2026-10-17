<?php

namespace App\Mcp\Tools;

use App\Services\MediaService;
use finfo;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

class UploadMediaTool extends AuthorizedTool
{
    protected array $permissions = [ 'nexopos.upload.medias' ];

    public string $name = 'upload_media';

    public string $description = 'Uploads a base64-encoded image or PDF to the media library.';

    public function schema( JsonSchema $schema ): array
    {
        return [
            'base64_file' => $schema->string()
                ->description( 'The base64-encoded content or data URI of a JPEG, PNG, GIF, WebP, or PDF file.' )
                ->required(),
            'custom_name' => $schema->string()
                ->description( 'An optional custom name for the uploaded file (excluding extension).' )
                ->nullable(),
        ];
    }

    public function handle( Request $request ): Response
    {
        $base64File = $request->get( 'base64_file' );
        $customName = $request->get( 'custom_name' );

        if ( ! is_string( $base64File ) || $base64File === '' ) {
            return Response::error( __( 'A base64-encoded file is required.' ) );
        }

        if ( preg_match( '/^data:[^;]+;base64,(.*)$/s', $base64File, $matches ) ) {
            $base64File = $matches[1];
        }

        $decodedFile = base64_decode( $base64File, true );

        if ( $decodedFile === false || strlen( $decodedFile ) > 10 * 1024 * 1024 ) {
            return Response::error( __( 'The file must be valid base64 and no larger than 10 MB.' ) );
        }

        $mimeType = ( new finfo( FILEINFO_MIME_TYPE ) )->buffer( $decodedFile );
        $allowedMimeTypes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
        ];

        if ( ! isset( $allowedMimeTypes[ $mimeType ] ) ) {
            return Response::error( __( 'Only JPEG, PNG, GIF, WebP, and PDF files are supported.' ) );
        }

        $extension = $allowedMimeTypes[ $mimeType ];
        $safeCustomName = is_string( $customName ) && $customName !== ''
            ? Str::slug( $customName )
            : null;
        $safeCustomName = $safeCustomName ?: null;
        $originalName = ( $safeCustomName ?: 'uploaded-file-' . uniqid() ) . '.' . $extension;
        $temporaryPath = storage_path( 'app/tmp/' . uniqid( 'media_', true ) . '.' . $extension );

        if ( ! File::exists( dirname( $temporaryPath ) ) ) {
            File::makeDirectory( dirname( $temporaryPath ), 0755, true );
        }

        File::put( $temporaryPath, $decodedFile );

        try {
            $uploadedFile = new UploadedFile(
                $temporaryPath,
                $originalName,
                $mimeType,
                null,
                true
            );
            $media = app( MediaService::class )->upload( $uploadedFile, $safeCustomName );
        } finally {
            File::delete( $temporaryPath );
        }

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
    }
}
