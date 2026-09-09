<?php

namespace Modules\NsOxen\Services;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductGallery;
use App\Models\User;
use App\Mcp\Tools\GenerateReportTool;
use App\Services\MediaService;
use Carbon\Carbon;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class SafeWriteTools
{
    private const GENERATED_IMAGE_MAX_BYTES = 12 * 1024 * 1024;

    private const MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp', 'application/pdf' => 'pdf'];

    public function __construct(
        private readonly MediaService $mediaService,
        private readonly OpenAIProvider $openAIProvider,
        private readonly ?GenerateReportTool $reportTool = null,
        private readonly ?MediaManagementService $mediaManagement = null,
    ) {}

    /** @return array{product_id: int, gallery_id: int, media_id: int, url: string} */
    public function generateProductImage( User $user, array $input ): array
    {
        $productId = (int) $input['product_id'];
        $provenance = (string) $input['idempotency_key'];
        $existingGallery = ProductGallery::query()->where( 'uuid', $provenance )->where( 'product_id', $productId )->first();
        if ( $existingGallery ) {
            return $this->galleryResult( $existingGallery );
        }

        $product = Product::query()->find( $productId )
            ?? throw new OxenException( 'NOT_FOUND', __m( 'Product not found in the current store.', 'NsOxen' ), 404 );
        $setting = \Modules\NsOxen\Models\Setting::query()->first();
        if ( ! $setting?->api_key ) {
            throw new OxenException( 'PROVIDER_UNAVAILABLE', __m( 'Configure an OpenAI API key before generating product images.', 'NsOxen' ), 503 );
        }

        $encodedImage = $this->openAIProvider->generateImage( $setting, (string) $input['prompt'] );
        $maximumEncodedBytes = (int) ceil( self::GENERATED_IMAGE_MAX_BYTES * 4 / 3 ) + 4;
        if ( strlen( $encodedImage ) > $maximumEncodedBytes ) {
            throw new OxenException( 'PROVIDER_INVALID_RESPONSE', __m( 'OpenAI returned an invalid or oversized product image.', 'NsOxen' ), 502 );
        }

        $decoded = base64_decode( $encodedImage, true );
        $imageInfo = $decoded === false ? false : @getimagesizefromstring( $decoded );
        if ( $decoded === false || strlen( $decoded ) > self::GENERATED_IMAGE_MAX_BYTES || $imageInfo === false || $imageInfo[0] !== 1024 || $imageInfo[1] !== 1024 || ( $imageInfo['mime'] ?? null ) !== 'image/png' ) {
            throw new OxenException( 'PROVIDER_INVALID_RESPONSE', __m( 'OpenAI returned an invalid or oversized product image.', 'NsOxen' ), 502 );
        }

        $path = tempnam( sys_get_temp_dir(), 'oxen-image-' );
        if ( $path === false || file_put_contents( $path, $decoded ) === false ) {
            throw new OxenException( 'INTERNAL_ERROR', __m( 'A temporary product image could not be created.', 'NsOxen' ), 500 );
        }

        try {
            $media = $this->mediaService->upload(
                new UploadedFile( $path, 'oxen-product-' . $productId . '.png', 'image/png', null, true ),
                'oxen-product-' . $productId . '-' . Str::lower( Str::random( 8 ) ),
            );
        } finally {
            @unlink( $path );
        }

        if ( ! $media instanceof Media ) {
            throw new OxenException( 'INTERNAL_ERROR', __m( 'The generated product image could not be uploaded.', 'NsOxen' ), 500 );
        }

        try {
            return DB::transaction( function () use ( $user, $productId, $provenance, $media ): array {
                $product = Product::query()->whereKey( $productId )->lockForUpdate()->first()
                    ?? throw new OxenException( 'NOT_FOUND', __m( 'Product not found in the current store.', 'NsOxen' ), 404 );
                $existingGallery = ProductGallery::query()->where( 'uuid', $provenance )->where( 'product_id', $productId )->lockForUpdate()->first();
                if ( $existingGallery ) {
                    $this->mediaService->deleteMedia( $media->id );

                    return $this->galleryResult( $existingGallery );
                }

                ProductGallery::query()->where( 'product_id', $productId )->where( 'featured', true )->update( ['featured' => false] );
                $gallery = new ProductGallery;
                $gallery->name = $media->name;
                $gallery->product_id = $productId;
                $gallery->media_id = $media->id;
                $gallery->url = (string) data_get( $media, 'sizes.original' );
                $gallery->order = (int) ProductGallery::query()->where( 'product_id', $productId )->max( 'order' ) + 1;
                $gallery->featured = true;
                $gallery->author_id = $user->id;
                $gallery->uuid = $provenance;
                $gallery->save();

                $product->thumbnail_id = $media->id;
                $product->save();

                return $this->galleryResult( $gallery );
            }, 3 );
        } catch ( \Throwable $exception ) {
            if ( Media::query()->whereKey( $media->id )->exists() ) {
                $this->mediaService->deleteMedia( $media->id );
            }
            throw $exception;
        }
    }

    public function upload( array $input ): array
    {
        $encoded = (string) ( $input['base64_content'] ?? '' );
        if ( preg_match( '/^data:[^;]+;base64,(.*)$/s', $encoded, $match ) === 1 ) {
            $encoded = $match[1];
        }
        $decoded = base64_decode( $encoded, true );
        if ( $decoded === false || strlen( $decoded ) > 2 * 1024 * 1024 ) {
            throw new OxenException( 'VALIDATION_FAILED', __m( 'Media must be valid base64 content no larger than 2 MB.', 'NsOxen' ) );
        }
        $mime = ( new finfo( FILEINFO_MIME_TYPE ) )->buffer( $decoded ) ?: '';
        $extension = self::MIMES[$mime] ?? throw new OxenException( 'VALIDATION_FAILED', __m( 'This media type is not allowed.', 'NsOxen' ) );
        $name = Str::slug( (string) ( $input['name'] ?? 'oxen-upload' ) ) . '.' . $extension;
        $path = tempnam( sys_get_temp_dir(), 'oxen-' );
        if ( $path === false ) {
            throw new OxenException( 'INTERNAL_ERROR', __m( 'A temporary upload could not be created.', 'NsOxen' ), 500 );
        } file_put_contents( $path, $decoded );
        try {
            $media = $this->mediaService->upload( new UploadedFile( $path, $name, $mime, null, true ), pathinfo( $name, PATHINFO_FILENAME ) );
            if ( ! $media ) {
                throw new OxenException( 'INTERNAL_ERROR', __m( 'Media upload failed.', 'NsOxen' ), 500 );
            }

            return $media->only( ['id', 'name', 'slug', 'url'] );
        } finally {
            @unlink( $path );
        }
    }

    public function updateMedia( User $user, array $input ): array
    {
        return ( $this->mediaManagement ?? app( MediaManagementService::class ) )->replace( $user, $input );
    }

    /**  array<string, mixed> */
    public function generateStoreLogo( User $user, array $input ): array
    {
        return ( $this->mediaManagement ?? app( MediaManagementService::class ) )->generateStoreLogo( $user, $input );
    }

    public function report( array $input ): array
    {
        try {
            foreach ( $input['sections'] as $index => $section ) {
                $type = (string) $section['type'];
                if ( $type === 'text' && ! filled( $section['content'] ?? null ) ) {
                    throw new \InvalidArgumentException( sprintf( __m( 'Report section %s requires text content.', 'NsOxen' ), $index + 1 ) );
                }
                if ( $type === 'table' && ( empty( $section['columns'] ) || ! isset( $section['rows'] ) ) ) {
                    throw new \InvalidArgumentException( sprintf( __m( 'Report section %s requires table columns and rows.', 'NsOxen' ), $index + 1 ) );
                }
                if ( in_array( $type, ['kpi_grid', 'bar_chart', 'pie_chart'], true ) && empty( $section['items'] ) ) {
                    throw new \InvalidArgumentException( sprintf( __m( 'Report section %s requires data items.', 'NsOxen' ), $index + 1 ) );
                }
            }

            if ( ( $input['format'] ?? 'pdf' ) === 'csv' ) {
                return $this->generateCsvReport( $input );
            }

            return ( $this->reportTool ?? app( GenerateReportTool::class ) )->generate( $input );
        } catch ( \InvalidArgumentException $exception ) {
            throw new OxenException( 'VALIDATION_FAILED', $exception->getMessage() );
        } catch ( OxenException $exception ) {
            throw $exception;
        } catch ( \Throwable $exception ) {
            report( $exception );
            throw new OxenException( 'REPORT_GENERATION_FAILED', __m( 'The report could not be generated.', 'NsOxen' ), 500 );
        }
    }

    /** @return array<string, mixed> */
    private function generateCsvReport( array $input ): array
    {
        $stream = fopen( 'php://temp', 'w+b' );
        if ( $stream === false ) {
            throw new \RuntimeException( __m( 'The CSV report could not be prepared.', 'NsOxen' ) );
        }

        try {
            fputcsv( $stream, [$this->safeCsvCell( $input['title'] )] );
            foreach ( ['subtitle', 'period_label', 'prepared_by'] as $field ) {
                if ( filled( $input[$field] ?? null ) ) {
                    fputcsv( $stream, [$this->safeCsvCell( Str::headline( $field ) ), $this->safeCsvCell( $input[$field] )] );
                }
            }

            foreach ( $input['sections'] as $section ) {
                fputcsv( $stream, [] );
                if ( filled( $section['title'] ?? null ) ) {
                    fputcsv( $stream, [$this->safeCsvCell( $section['title'] )] );
                }
                if ( filled( $section['description'] ?? null ) ) {
                    fputcsv( $stream, [$this->safeCsvCell( $section['description'] )] );
                }

                if ( $section['type'] === 'text' ) {
                    fputcsv( $stream, [$this->safeCsvCell( $section['content'] ?? '' )] );
                } elseif ( $section['type'] === 'table' ) {
                    $columns = array_values( $section['columns'] ?? [] );
                    fputcsv( $stream, array_map( $this->safeCsvCell( ... ), $columns ) );
                    foreach ( $section['rows'] ?? [] as $row ) {
                        $values = array_is_list( $row ) ? $row : array_map( static fn ( string $column ): mixed => $row[$column] ?? null, $columns );
                        fputcsv( $stream, array_map( $this->safeCsvCell( ... ), $values ) );
                    }
                } elseif ( in_array( $section['type'], ['kpi_grid', 'bar_chart', 'pie_chart'], true ) ) {
                    fputcsv( $stream, ['Label', 'Value', 'Detail'] );
                    foreach ( $section['items'] ?? [] as $item ) {
                        fputcsv( $stream, array_map( $this->safeCsvCell( ... ), [$item['label'], $item['value'], $item['detail'] ?? null] ) );
                    }
                }
            }

            rewind( $stream );
            $contents = stream_get_contents( $stream );
        } finally {
            fclose( $stream );
        }

        if ( $contents === false ) {
            throw new \RuntimeException( __m( 'The CSV report could not be prepared.', 'NsOxen' ) );
        }

        $expiresAt = Carbon::now()->addMinutes( (int) ( $input['expires_in_minutes'] ?? 120 ) );
        $base = Str::slug( (string) ( $input['filename'] ?? $input['title'] ) ) ?: 'report';
        $filename = $base . '-' . now()->format( 'Ymd-His' ) . '-' . Str::lower( Str::random( 6 ) ) . '.csv';
        if ( ! Storage::disk( 'ns-temp' )->put( 'mcp-reports/' . $filename, "\xEF\xBB\xBF" . $contents ) ) {
            throw new \RuntimeException( __m( 'The CSV report could not be stored.', 'NsOxen' ) );
        }

        return [
            'status' => 'success',
            'format' => 'csv',
            'filename' => $filename,
            'download_url' => URL::temporarySignedRoute( 'mcp.reports.download', $expiresAt, ['filename' => $filename] ),
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }

    private function safeCsvCell( mixed $value ): string
    {
        $cell = (string) ( $value ?? '' );

        return preg_match( '/^[=+\-@\t\r]/', $cell ) === 1 ? "'" . $cell : $cell;
    }

    public function deleteMedia( User $user, array $input ): array
    {
        $media = Media::query()->find( (int) ( $input['media_id'] ?? $input['id'] ?? 0 ) ) ?? throw new OxenException( 'NOT_FOUND', __m( 'Media not found.', 'NsOxen' ), 404 );
        $this->mediaService->deleteMedia( $media->id );

        return ['deleted' => true, 'media_id' => $media->id];
    }

    /** @return array{product_id: int, gallery_id: int, media_id: int, url: string} */
    private function galleryResult( ProductGallery $gallery ): array
    {
        return [
            'product_id' => (int) $gallery->product_id,
            'gallery_id' => (int) $gallery->id,
            'media_id' => (int) $gallery->media_id,
            'url' => (string) $gallery->url,
        ];
    }
}
