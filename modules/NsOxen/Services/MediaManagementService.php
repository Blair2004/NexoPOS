<?php

namespace Modules\NsOxen\Services;

use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;
use finfo;
use Gumlet\ImageResize;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\NsOxen\Models\Setting;

class MediaManagementService
{
    private const GENERATED_IMAGE_MAX_BYTES = 12 * 1024 * 1024;

    public function __construct(
        private readonly MediaService $mediaService,
        private readonly OpenAIProvider $openAIProvider,
    ) {}

    /** @return array{id: int, name: string, slug: string, extension: string, url: string} */
    public function replace( User $user, array $input ): array
    {
        $media = Media::query()->find( (int) $input['media_id'] )
            ?? throw new OxenException( 'NOT_FOUND', __m( 'Media not found.', 'NsOxen' ), 404 );
        $decoded = $this->decodeMedia( (string) $input['base64_content'] );
        $mime = ( new finfo( FILEINFO_MIME_TYPE ) )->buffer( $decoded ) ?: '';
        $compatibleExtensions = [
            'image/jpeg' => ['jpg', 'jpeg'], 'image/png' => ['png'], 'image/gif' => ['gif'],
            'image/webp' => ['webp'], 'application/pdf' => ['pdf'],
        ];
        if ( ! in_array( strtolower( (string) $media->extension ), $compatibleExtensions[$mime] ?? [], true ) ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'The replacement media type must match the existing file extension.', 'NsOxen' ) );
        }

        $disk = Storage::disk( 'public' );
        $original = $media->slug . '.' . $media->extension;
        if ( ! $disk->exists( $original ) ) {
            throw new OxenException( 'NOT_FOUND', __m( 'The existing media file could not be found.', 'NsOxen' ), 404 );
        }
        $suffix = '.oxen-' . Str::lower( Str::random( 12 ) );
        $stagedOriginal = $media->slug . $suffix . '.' . $media->extension;
        $backupOriginal = $media->slug . $suffix . '.backup.' . $media->extension;
        $thumbnail = $media->slug . '-thumb.' . $media->extension;
        $stagedThumbnail = $media->slug . $suffix . '-thumb.' . $media->extension;
        $backupThumbnail = $media->slug . $suffix . '-thumb.backup.' . $media->extension;
        $isThumbnailImage = in_array( strtolower( (string) $media->extension ), ['png', 'jpg', 'jpeg', 'gif'], true );

        try {
            if ( ! $disk->put( $stagedOriginal, $decoded ) ) {
                throw new \RuntimeException( 'Unable to stage media.' );
            }
            if ( $isThumbnailImage ) {
                $image = new ImageResize( $disk->path( $stagedOriginal ) );
                $image->resizeToBestFit( 280, 181 );
                $image->save( $disk->path( $stagedThumbnail ) );
                if ( ! $disk->exists( $stagedThumbnail ) ) {
                    throw new \RuntimeException( 'Unable to stage media thumbnail.' );
                }
            }
            if ( ! $disk->move( $original, $backupOriginal ) ) {
                throw new \RuntimeException( 'Unable to back up media.' );
            }
            if ( $isThumbnailImage && $disk->exists( $thumbnail ) && ! $disk->move( $thumbnail, $backupThumbnail ) ) {
                throw new \RuntimeException( 'Unable to back up media thumbnail.' );
            }
            if ( ! $disk->move( $stagedOriginal, $original ) ) {
                throw new \RuntimeException( 'Unable to replace media.' );
            }
            if ( $isThumbnailImage && ! $disk->move( $stagedThumbnail, $thumbnail ) ) {
                throw new \RuntimeException( 'Unable to replace media thumbnail.' );
            }

            $media->user_id = $user->id;
            $media->save();
            $disk->delete( [$backupOriginal, $backupThumbnail] );
        } catch ( \Throwable $exception ) {
            $disk->delete( [$stagedOriginal, $stagedThumbnail] );
            if ( $disk->exists( $backupOriginal ) ) {
                $disk->delete( $original );
                $disk->move( $backupOriginal, $original );
            }
            if ( $disk->exists( $backupThumbnail ) ) {
                $disk->delete( $thumbnail );
                $disk->move( $backupThumbnail, $thumbnail );
            }
            report( $exception );
            throw new OxenException( 'MEDIA_REPLACEMENT_FAILED', __m( 'The media could not be replaced safely.', 'NsOxen' ), 500 );
        }

        $media = $this->mediaService->find( $media->id ) ?: $media;

        return [
            'id' => (int) $media->id, 'name' => (string) $media->name,
            'slug' => (string) $media->slug, 'extension' => (string) $media->extension,
            'url' => (string) data_get( $media, 'sizes.original', $disk->url( $original ) ),
        ];
    }

    /** @return array<string, mixed> */
    public function generateStoreLogo( User $user, array $input ): array
    {
        $setting = Setting::query()->first();
        if ( ! $setting?->api_key ) {
            throw new OxenException( 'PROVIDER_UNAVAILABLE', __m( 'Configure an OpenAI API key before generating a store logo.', 'NsOxen' ), 503 );
        }

        $media = [];
        try {
            foreach ( ['square' => '1024x1024', 'landscape' => '1536x1024'] as $variant => $size ) {
                $encoded = $this->openAIProvider->generateImage( $setting, (string) $input['prompt'], $size, true );
                $decoded = $this->decodeGeneratedPng( $encoded, $size );
                $media[$variant] = $this->uploadGeneratedPng( $user, $decoded, 'oxen-store-logo-' . $variant );
            }

            $squareUrl = (string) data_get( $media['square'], 'sizes.original' );
            $landscapeUrl = (string) data_get( $media['landscape'], 'sizes.original' );
            if ( $squareUrl === '' || $landscapeUrl === '' ) {
                throw new \RuntimeException( 'Generated logo URLs are missing.' );
            }

            $optionKeys = ['ns_store_square_logo', 'ns_store_rectangle_logo', 'ns_invoice_receipt_logo'];
            $previous = (array) ns()->option->get( $optionKeys );
            try {
                ns()->option->set( 'ns_store_square_logo', $squareUrl );
                ns()->option->set( 'ns_store_rectangle_logo', $landscapeUrl );
                ns()->option->set( 'ns_invoice_receipt_logo', $landscapeUrl );
            } catch ( \Throwable $exception ) {
                foreach ( $optionKeys as $key ) {
                    ns()->option->set( $key, $previous[$key] ?? null );
                }
                throw $exception;
            }

            return [
                'square_media_id' => (int) $media['square']->id, 'square_url' => $squareUrl,
                'landscape_media_id' => (int) $media['landscape']->id, 'landscape_url' => $landscapeUrl,
                'applied_options' => $optionKeys,
            ];
        } catch ( \Throwable $exception ) {
            foreach ( $media as $createdMedia ) {
                if ( $createdMedia instanceof Media && Media::query()->whereKey( $createdMedia->id )->exists() ) {
                    $this->mediaService->deleteMedia( $createdMedia->id );
                }
            }
            if ( $exception instanceof OxenException ) {
                throw $exception;
            }
            report( $exception );
            throw new OxenException( 'LOGO_GENERATION_FAILED', __m( 'The store logo could not be generated and assigned safely.', 'NsOxen' ), 500 );
        }
    }

    private function decodeMedia( string $encoded ): string
    {
        if ( preg_match( '/^data:[^;]+;base64,(.*)$/s', $encoded, $match ) === 1 ) {
            $encoded = $match[1];
        }
        $maximumEncodedBytes = (int) ceil( 2 * 1024 * 1024 * 4 / 3 ) + 4;
        $decoded = strlen( $encoded ) <= $maximumEncodedBytes ? base64_decode( $encoded, true ) : false;
        if ( $decoded === false || strlen( $decoded ) > 2 * 1024 * 1024 ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'Media must be valid base64 content no larger than 2 MB.', 'NsOxen' ) );
        }

        return $decoded;
    }

    private function decodeGeneratedPng( string $encoded, string $size ): string
    {
        $maximumEncodedBytes = (int) ceil( self::GENERATED_IMAGE_MAX_BYTES * 4 / 3 ) + 4;
        $decoded = strlen( $encoded ) <= $maximumEncodedBytes ? base64_decode( $encoded, true ) : false;
        $imageInfo = $decoded === false ? false : @getimagesizefromstring( $decoded );
        [$width, $height] = array_map( 'intval', explode( 'x', $size ) );
        $hasAlpha = is_string( $decoded ) && strlen( $decoded ) > 25 && in_array( ord( $decoded[25] ), [4, 6], true );
        if ( $decoded === false || strlen( $decoded ) > self::GENERATED_IMAGE_MAX_BYTES || $imageInfo === false || $imageInfo[0] !== $width || $imageInfo[1] !== $height || ( $imageInfo['mime'] ?? null ) !== 'image/png' || ! $hasAlpha ) {
            throw new OxenException( 'PROVIDER_INVALID_RESPONSE', __m( 'OpenAI returned an invalid, opaque, or oversized logo image.', 'NsOxen' ), 502 );
        }

        return $decoded;
    }

    private function uploadGeneratedPng( User $user, string $decoded, string $name ): Media
    {
        $path = tempnam( sys_get_temp_dir(), 'oxen-logo-' );
        if ( $path === false || file_put_contents( $path, $decoded ) === false ) {
            throw new \RuntimeException( 'A temporary logo could not be created.' );
        }
        try {
            $media = $this->mediaService->upload( new UploadedFile( $path, $name . '.png', 'image/png', null, true ), $name . '-' . Str::lower( Str::random( 8 ) ) );
        } finally {
            @unlink( $path );
        }
        if ( ! $media instanceof Media ) {
            throw new \RuntimeException( 'A generated logo could not be uploaded.' );
        }
        $sizes = $media->sizes ?? null;
        unset( $media->sizes );
        $media->user_id = $user->id;
        $media->save();
        $media->sizes = $sizes;

        return $media;
    }
}
