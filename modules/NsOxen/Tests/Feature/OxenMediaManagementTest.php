<?php

namespace Modules\NsOxen\Tests\Feature;

use App\Models\Media;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Modules\NsOxen\Models\Setting;
use Modules\NsOxen\Services\MediaManagementService;
use Modules\NsOxen\Services\OpenAIProvider;
use Modules\NsOxen\Services\OxenException;
use Tests\TestCase;

class OxenMediaManagementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_image_content_is_replaced_without_changing_media_identity_or_url(): void
    {
        Storage::fake( 'public' );
        $user = User::query()->firstOrFail();
        $media = Media::unguarded( fn (): Media => Media::query()->create( ['name' => 'oxen-existing', 'extension' => 'png', 'slug' => '2026/09/oxen-existing', 'user_id' => $user->id] ) );
        $old = $this->png( 40, 40 );
        $replacement = $this->png( 80, 60 );
        Storage::disk( 'public' )->put( $media->slug . '.png', $old );
        Storage::disk( 'public' )->put( $media->slug . '-thumb.png', $old );

        $result = app( MediaManagementService::class )->replace( $user, ['media_id' => $media->id, 'base64_content' => base64_encode( $replacement )] );

        $this->assertSame( $media->id, $result['id'] );
        $this->assertSame( $media->slug, $result['slug'] );
        $this->assertSame( 'png', $result['extension'] );
        $this->assertSame( $replacement, Storage::disk( 'public' )->get( $media->slug . '.png' ) );
        Storage::disk( 'public' )->assertExists( $media->slug . '-thumb.png' );
        $this->assertStringEndsWith( $media->slug . '.png', $result['url'] );
    }

    public function test_replacement_rejects_mime_mismatch_before_touching_existing_file(): void
    {
        Storage::fake( 'public' );
        $user = User::query()->firstOrFail();
        $media = Media::unguarded( fn (): Media => Media::query()->create( ['name' => 'oxen-pdf', 'extension' => 'pdf', 'slug' => '2026/09/oxen-pdf', 'user_id' => $user->id] ) );
        Storage::disk( 'public' )->put( $media->slug . '.pdf', '%PDF-1.4 original' );

        try {
            app( MediaManagementService::class )->replace( $user, ['media_id' => $media->id, 'base64_content' => base64_encode( $this->png( 10, 10 ) )] );
            $this->fail( 'A MIME mismatch unexpectedly replaced the media.' );
        } catch ( OxenException $exception ) {
            $this->assertSame( 'VALIDATION_FAILED', $exception->errorCode );
        }

        $this->assertSame( '%PDF-1.4 original', Storage::disk( 'public' )->get( $media->slug . '.pdf' ) );
    }

    public function test_store_logo_generates_both_transparent_sizes_before_assigning_options(): void
    {
        ( require dirname( __DIR__, 2 ) . '/Migrations/2026_09_04_000000_create_oxen_tables.php' )->up();
        $user = User::query()->firstOrFail();
        Setting::query()->updateOrCreate( ['provider' => 'openai'], ['api_key' => 'sk-test', 'image_model' => 'gpt-image-2'] );
        $provider = Mockery::mock( OpenAIProvider::class );
        $provider->shouldReceive( 'generateImage' )->once()->ordered()->with( Mockery::type( Setting::class ), 'Oxen Market', '1024x1024', true )->andReturn( base64_encode( $this->png( 1024, 1024, true ) ) );
        $provider->shouldReceive( 'generateImage' )->once()->ordered()->with( Mockery::type( Setting::class ), 'Oxen Market', '1536x1024', true )->andReturn( base64_encode( $this->png( 1536, 1024, true ) ) );
        $square = $this->media( $user, 'oxen-square', '/storage/oxen-square.png' );
        $landscape = $this->media( $user, 'oxen-landscape', '/storage/oxen-landscape.png' );
        $mediaService = Mockery::mock( MediaService::class );
        $mediaService->shouldReceive( 'upload' )->twice()->andReturn( $square, $landscape );
        $mediaService->shouldNotReceive( 'deleteMedia' );

        $result = ( new MediaManagementService( $mediaService, $provider ) )->generateStoreLogo( $user, ['prompt' => 'Oxen Market'] );

        $this->assertSame( $square->id, $result['square_media_id'] );
        $this->assertSame( $landscape->id, $result['landscape_media_id'] );
        $this->assertSame( '/storage/oxen-square.png', ns()->option->get( 'ns_store_square_logo' ) );
        $this->assertSame( '/storage/oxen-landscape.png', ns()->option->get( 'ns_store_rectangle_logo' ) );
        $this->assertSame( '/storage/oxen-landscape.png', ns()->option->get( 'ns_invoice_receipt_logo' ) );
    }

    private function media( User $user, string $name, string $url ): Media
    {
        $media = Media::unguarded( fn (): Media => Media::query()->create( ['name' => $name, 'extension' => 'png', 'slug' => '2026/09/' . $name, 'user_id' => $user->id] ) );
        $media->sizes = (object) ['original' => $url];

        return $media;
    }

    private function png( int $width, int $height, bool $transparent = false ): string
    {
        $image = imagecreatetruecolor( $width, $height );
        if ( $transparent ) {
            imagealphablending( $image, false );
            imagesavealpha( $image, true );
            imagefill( $image, 0, 0, imagecolorallocatealpha( $image, 255, 255, 255, 127 ) );
        }
        ob_start();
        imagepng( $image );
        $contents = ob_get_clean();
        imagedestroy( $image );

        return (string) $contents;
    }
}
